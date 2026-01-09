import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import axios from 'axios'
import { DashboardLayout } from '../components/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card'
import { Button } from '../components/ui/button'
import { Badge } from '../components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../components/ui/table'
import { Loader2, CalendarClock, QrCode, Users, CheckCircle2, XCircle, RefreshCw } from 'lucide-react'

const api = axios.create({ baseURL: '/api' })

interface SessionRow {
  id: number
  token: string
  expires_at: string
  created_at: string
  created_by_email: string
  course_id: number
  course_name?: string
  is_expired?: number
  scanned_count?: number
  enrolled_count?: number
}

interface RosterRow {
  student_id: number
  student_name: string
  student_email: string
  scanned_at: string | null
  present: number
}

export default function AttendanceSessions() {
  const navigate = useNavigate()
  const { courseId } = useParams<{ courseId: string }>()
  const [user, setUser] = useState<{ role: string; name: string; email?: string } | null>(null)
  const [sessions, setSessions] = useState<SessionRow[]>([])
  const [loading, setLoading] = useState(true)
  const [selectedSession, setSelectedSession] = useState<SessionRow | null>(null)
  const [roster, setRoster] = useState<RosterRow[]>([])
  const [rosterLoading, setRosterLoading] = useState(false)

  useEffect(() => {
    const storedUser = localStorage.getItem('user')
    if (!storedUser) {
      navigate('/login')
      return
    }
    const parsed = JSON.parse(storedUser)
    setUser(parsed)

    // All authenticated users can view sessions (students only for enrolled courses)
  }, [navigate])

  useEffect(() => {
    if (courseId && user) {
      loadSessions()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [courseId, user?.role])

  const loadSessions = async () => {
    if (!courseId || !user) return
    setLoading(true)
    try {
      const params: any = {
        course_id: Number(courseId),
        requested_by_role: user.role,
      }
      
      // Add student_email for students
      if (user.role.toLowerCase() === 'student' && user.email) {
        params.student_email = user.email
      }
      
      const res = await api.get('/courses/attendance-sessions', { params })
      if (res.data?.success) {
        setSessions(res.data.data || [])
        if (res.data.data?.length) {
          selectSession(res.data.data[0])
        } else {
          setSelectedSession(null)
          setRoster([])
        }
      }
    } catch (err: any) {
      console.error('Failed to load sessions', err)
      alert(err?.response?.data?.message || 'Failed to load sessions')
    } finally {
      setLoading(false)
    }
  }

  const selectSession = async (session: SessionRow) => {
    setSelectedSession(session)
    await loadRoster(session)
  }

  const loadRoster = async (session: SessionRow) => {
    if (!user) return
    // Students don't need to see the full roster
    if (user.role.toLowerCase() === 'student') {
      setRoster([])
      return
    }
    setRosterLoading(true)
    try {
      const res = await api.get('/courses/attendance-logs', {
        params: {
          requested_by_role: user.role,
          session_id: session.id,
          include_students: 1,
        }
      })
      if (res.data?.success) {
        setRoster(res.data.data?.roster || [])
      }
    } catch (err) {
      console.error('Failed to load roster', err)
    } finally {
      setRosterLoading(false)
    }
  }

  const presentCount = roster.filter(r => r.present === 1).length
  const enrolledCount = selectedSession?.enrolled_count ?? roster.length

  return (
    <DashboardLayout
      userRole={user?.role || ''}
      name={user?.name || ''}
      onLogout={() => {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        navigate('/login')
      }}
      title="Attendance Sessions"
    >
      <div className="grid gap-4 md:grid-cols-[1.1fr_1fr]">
        <Card className="h-full">
          <CardHeader className="flex items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <CalendarClock className="h-5 w-5" />
              Sessions
            </CardTitle>
            <Button variant="outline" size="sm" onClick={loadSessions} disabled={loading}>
              <RefreshCw className="mr-2 h-4 w-4" />
              Refresh
            </Button>
          </CardHeader>
          <CardContent className="space-y-2">
            {loading ? (
              <div className="flex items-center gap-2 text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Loading sessions...
              </div>
            ) : sessions.length === 0 ? (
              <div className="text-muted-foreground text-sm">
                No sessions yet. Generate an attendance QR to create the first session.
              </div>
            ) : (
              <div className="rounded-md border">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Created</TableHead>
                      <TableHead>Expires</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Scanned</TableHead>
                      <TableHead></TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {sessions.map((s) => {
                      const expired = s.is_expired === 1 || new Date(s.expires_at).getTime() <= Date.now()
                      return (
                        <TableRow
                          key={s.id}
                          className="cursor-pointer"
                          onClick={() => selectSession(s)}
                        >
                          <TableCell>{new Date(s.created_at).toLocaleString()}</TableCell>
                          <TableCell>{new Date(s.expires_at).toLocaleString()}</TableCell>
                          <TableCell>
                            <Badge variant={expired ? 'destructive' : 'secondary'}>
                              {expired ? 'Expired' : 'Active'}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            {s.scanned_count ?? 0}/{s.enrolled_count ?? 0}
                          </TableCell>
                          <TableCell className="text-right">
                            <Button size="sm" variant="ghost" onClick={(e) => { e.stopPropagation(); selectSession(s) }}>
                              View
                            </Button>
                          </TableCell>
                        </TableRow>
                      )
                    })}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>
        </Card>

        {user?.role.toLowerCase() !== 'student' && (
          <Card className="h-full">
            <CardHeader className="flex items-center justify-between">
              <CardTitle className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                Session Attendees
              </CardTitle>
              {selectedSession && (
                <Badge variant="outline">
                  {presentCount}/{enrolledCount || '—'} present
                </Badge>
              )}
            </CardHeader>
            <CardContent className="space-y-3">
              {!selectedSession ? (
                <div className="text-muted-foreground text-sm">Select a session to view attendance.</div>
              ) : rosterLoading ? (
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Loading roster...
                </div>
              ) : roster.length === 0 ? (
                <div className="text-muted-foreground text-sm">No enrolled students or no scans yet.</div>
              ) : (
                <div className="rounded-md border">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Student</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Scanned At</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {roster.map((r) => (
                        <TableRow key={r.student_id}>
                          <TableCell>{r.student_name}</TableCell>
                          <TableCell>{r.student_email}</TableCell>
                          <TableCell>
                            {r.present === 1 ? (
                              <span className="flex items-center gap-1 text-green-600">
                                <CheckCircle2 className="h-4 w-4" />
                                Present
                              </span>
                            ) : (
                              <span className="flex items-center gap-1 text-muted-foreground">
                                <XCircle className="h-4 w-4" />
                                Absent
                              </span>
                            )}
                          </TableCell>
                          <TableCell>
                            {r.scanned_at ? new Date(r.scanned_at).toLocaleString() : '—'}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
              {selectedSession && (
                <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                  <Badge variant={selectedSession.is_expired ? 'destructive' : 'secondary'}>
                    {selectedSession.is_expired ? 'Expired' : 'Active'}
                  </Badge>
                  <span>Created: {new Date(selectedSession.created_at).toLocaleString()}</span>
                  <span>Expires: {new Date(selectedSession.expires_at).toLocaleString()}</span>
                </div>
              )}
              <div className="flex gap-2">
                <Button variant="outline" onClick={() => navigate(-1)}>Back</Button>
                {selectedSession && ['admin', 'instructor'].includes(user?.role.toLowerCase() || '') && (
                  <Button
                    variant="secondary"
                    onClick={() => navigate(`/dashboard/courses/${selectedSession.course_id}/attendance-display`)}
                  >
                    <QrCode className="mr-2 h-4 w-4" />
                    Show QR
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        )}
        
        {user?.role.toLowerCase() === 'student' && (
          <Card className="h-full">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <CalendarClock className="h-5 w-5" />
                Session Information
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              {!selectedSession ? (
                <div className="text-muted-foreground text-sm">Select a session to view details.</div>
              ) : (
                <>
                  <div className="space-y-2">
                    <div>
                      <p className="text-sm font-medium text-muted-foreground">Status</p>
                      <Badge variant={selectedSession.is_expired ? 'destructive' : 'secondary'} className="mt-1">
                        {selectedSession.is_expired ? 'Expired' : 'Active'}
                      </Badge>
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground">Created</p>
                      <p className="text-sm">{new Date(selectedSession.created_at).toLocaleString()}</p>
                    </div>
                    <div>
                      <p className="text-sm font-medium text-muted-foreground">Expires</p>
                      <p className="text-sm">{new Date(selectedSession.expires_at).toLocaleString()}</p>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" onClick={() => navigate(-1)}>Back</Button>
                  </div>
                </>
              )}
            </CardContent>
          </Card>
        )}
      </div>
    </DashboardLayout>
  )
}

