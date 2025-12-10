import { useEffect, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import axios from 'axios'
import { DashboardLayout } from '../components/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../components/ui/table'
import { Badge } from '../components/ui/badge'
import { Button } from '../components/ui/button'
import { Loader2, TrendingUp, Users, CalendarClock, BarChart, QrCode } from 'lucide-react'

const api = axios.create({ baseURL: '/api' })

interface Summary {
  sessions_count: number
  active_sessions: number
  enrolled_count: number
  last_session_at: string | null
}

interface SessionRow {
  id: number
  token: string
  created_at: string
  expires_at: string
  created_by_email: string
  is_expired?: number
  scanned_count?: number
  enrolled_count?: number
}

interface StudentRow {
  student_id: number
  student_name: string
  student_email: string
  total_sessions: number
  attended_sessions: number
}

export default function AttendanceAnalytics() {
  const navigate = useNavigate()
  const { courseId } = useParams<{ courseId: string }>()
  const [user, setUser] = useState<{ role: string; name: string; email?: string } | null>(null)
  const [summary, setSummary] = useState<Summary | null>(null)
  const [sessions, setSessions] = useState<SessionRow[]>([])
  const [students, setStudents] = useState<StudentRow[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const storedUser = localStorage.getItem('user')
    if (!storedUser) {
      navigate('/login')
      return
    }
    const parsed = JSON.parse(storedUser)
    setUser(parsed)
    if (!['admin', 'instructor'].includes(parsed.role.toLowerCase())) {
      navigate('/dashboard')
      return
    }
  }, [navigate])

  useEffect(() => {
    if (courseId && user) {
      loadAnalytics()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [courseId, user?.role])

  const loadAnalytics = async () => {
    if (!courseId || !user) return
    setLoading(true)
    try {
      const res = await api.get('/courses/attendance-analytics', {
        params: {
          course_id: Number(courseId),
          requested_by_role: user.role,
        }
      })
      if (res.data?.success) {
        setSummary(res.data.data?.summary || null)
        setSessions(res.data.data?.sessions || [])
        setStudents(res.data.data?.students || [])
      }
    } catch (err) {
      console.error('Failed to load analytics', err)
    } finally {
      setLoading(false)
    }
  }

  const averageAttendance = useMemo(() => {
    if (!sessions.length) return 0
    let totalRate = 0
    sessions.forEach((s) => {
      const enrolled = s.enrolled_count ?? 0
      const scanned = s.scanned_count ?? 0
      const rate = enrolled > 0 ? (scanned / enrolled) * 100 : 0
      totalRate += rate
    })
    return Math.round(totalRate / sessions.length)
  }, [sessions])

  return (
    <DashboardLayout
      userRole={user?.role || ''}
      name={user?.name || ''}
      onLogout={() => {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        navigate('/login')
      }}
      title="Attendance Analytics"
    >
      <div className="flex flex-col gap-4">
        <div className="flex flex-wrap gap-3">
          <Button variant="outline" onClick={() => navigate(-1)}>Back</Button>
          <Button variant="secondary" onClick={() => navigate(`/dashboard/courses/${courseId}/attendance-sessions`)}>
            <CalendarClock className="mr-2 h-4 w-4" />
            Sessions
          </Button>
          <Button variant="secondary" onClick={() => navigate(`/dashboard/courses/${courseId}/attendance-display`)}>
            <QrCode className="mr-2 h-4 w-4" />
            Fullscreen QR
          </Button>
        </div>

        {loading ? (
          <div className="flex items-center gap-2 text-muted-foreground">
            <Loader2 className="h-4 w-4 animate-spin" />
            Loading analytics...
          </div>
        ) : (
          <>
            <div className="grid gap-4 md:grid-cols-3">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <CalendarClock className="h-5 w-5" />
                    Sessions
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-1">
                  <div className="text-3xl font-bold">{summary?.sessions_count ?? 0}</div>
                  <div className="text-sm text-muted-foreground">
                    Active: {summary?.active_sessions ?? 0} · Last: {summary?.last_session_at ? new Date(summary.last_session_at).toLocaleString() : '—'}
                  </div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Users className="h-5 w-5" />
                    Enrolled
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-1">
                  <div className="text-3xl font-bold">{summary?.enrolled_count ?? 0}</div>
                  <div className="text-sm text-muted-foreground">Students enrolled in this course</div>
                </CardContent>
              </Card>
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <BarChart className="h-5 w-5" />
                    Avg Attendance
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-1">
                  <div className="text-3xl font-bold">{averageAttendance}%</div>
                  <div className="text-sm text-muted-foreground">Average per session</div>
                </CardContent>
              </Card>
            </div>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <TrendingUp className="h-5 w-5" />
                  Sessions History
                </CardTitle>
              </CardHeader>
              <CardContent>
                {sessions.length === 0 ? (
                  <div className="text-sm text-muted-foreground">No sessions yet.</div>
                ) : (
                  <div className="rounded-md border overflow-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Created</TableHead>
                          <TableHead>Expires</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead>Scanned</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {sessions.map((s) => {
                          const expired = s.is_expired === 1 || new Date(s.expires_at).getTime() <= Date.now()
                          return (
                            <TableRow key={s.id}>
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
                            </TableRow>
                          )
                        })}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Users className="h-5 w-5" />
                  Student Attendance Rates
                </CardTitle>
              </CardHeader>
              <CardContent>
                {students.length === 0 ? (
                  <div className="text-sm text-muted-foreground">No enrolled students.</div>
                ) : (
                  <div className="rounded-md border overflow-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Student</TableHead>
                          <TableHead>Email</TableHead>
                          <TableHead>Attended</TableHead>
                          <TableHead>Rate</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {students.map((st) => {
                          const rate = st.total_sessions > 0 ? Math.round((st.attended_sessions / st.total_sessions) * 100) : 0
                          return (
                            <TableRow key={st.student_id}>
                              <TableCell>{st.student_name}</TableCell>
                              <TableCell>{st.student_email}</TableCell>
                              <TableCell>{st.attended_sessions}/{st.total_sessions}</TableCell>
                              <TableCell>{rate}%</TableCell>
                            </TableRow>
                          )
                        })}
                      </TableBody>
                    </Table>
                  </div>
                )}
              </CardContent>
            </Card>
          </>
        )}
      </div>
    </DashboardLayout>
  )
}

