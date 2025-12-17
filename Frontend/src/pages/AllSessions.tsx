import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { DashboardLayout } from '../components/DashboardLayout'
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card'
import { Button } from '../components/ui/button'
import { Badge } from '../components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../components/ui/table'
import { Loader2, CalendarClock, BookOpen, RefreshCw, ExternalLink, ChevronDown, ChevronRight, Users, CheckCircle2 } from 'lucide-react'

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

interface AttendanceLog {
  id: number
  student_id: number
  student_name: string
  student_email: string
  scanned_at: string
}

export default function AllSessions() {
  const navigate = useNavigate()
  const [user, setUser] = useState<{ role: string; name: string; email?: string } | null>(null)
  const [sessions, setSessions] = useState<SessionRow[]>([])
  const [loading, setLoading] = useState(true)
  const [expandedSessions, setExpandedSessions] = useState<Set<number>>(new Set())
  const [attendanceLogs, setAttendanceLogs] = useState<Record<number, AttendanceLog[]>>({})
  const [loadingLogs, setLoadingLogs] = useState<Set<number>>(new Set())

  useEffect(() => {
    const storedUser = localStorage.getItem('user')
    if (!storedUser) {
      navigate('/login')
      return
    }
    const parsed = JSON.parse(storedUser)
    setUser(parsed)

    // All authenticated users can view sessions (students see only enrolled courses)
  }, [navigate])

  useEffect(() => {
    if (user) {
      loadSessions()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [user?.role])

  const loadSessions = async () => {
    if (!user) return
    setLoading(true)
    try {
      // For students, first get their enrolled courses, then get sessions for those courses
      if (user.role.toLowerCase() === 'student' && user.email) {
        // Get enrolled courses
        const coursesRes = await api.get('/courses', {
          params: {
            requested_by_role: user.role,
            student_email: user.email,
          }
        })
        
        if (coursesRes.data?.success && coursesRes.data.data?.length > 0) {
          const enrolledCourses = coursesRes.data.data
          // Get sessions for each enrolled course
          const allSessions: SessionRow[] = []
          for (const course of enrolledCourses) {
            try {
              const sessionsRes = await api.get('/courses/attendance-sessions', {
                params: {
                  course_id: course.id,
                  requested_by_role: user.role,
                  student_email: user.email,
                }
              })
              if (sessionsRes.data?.success && sessionsRes.data.data) {
                allSessions.push(...sessionsRes.data.data)
              }
            } catch (err) {
              console.error(`Failed to load sessions for course ${course.id}:`, err)
            }
          }
          setSessions(allSessions)
        } else {
          setSessions([])
        }
      } else {
        // Admin and instructor can see all sessions
        const res = await api.get('/courses/attendance-sessions', {
          params: {
            requested_by_role: user.role,
            // No course_id - will fetch all sessions
          }
        })
        if (res.data?.success) {
          setSessions(res.data.data || [])
        } else {
          console.error('API returned unsuccessful response:', res.data)
        }
      }
    } catch (err: any) {
      console.error('Failed to load sessions', err)
      if (err.response) {
        console.error('Error response:', err.response.status, err.response.data)
      }
    } finally {
      setLoading(false)
    }
  }

  const loadAttendanceLogs = async (sessionId: number) => {
    if (!user || attendanceLogs[sessionId]) return // Already loaded
    
    // Students shouldn't see other students' attendance logs
    if (user.role.toLowerCase() === 'student') {
      return
    }
    
    setLoadingLogs(prev => new Set(prev).add(sessionId))
    try {
      const res = await api.get('/courses/attendance-logs', {
        params: {
          requested_by_role: user.role,
          session_id: sessionId,
        }
      })
      if (res.data?.success) {
        setAttendanceLogs(prev => ({
          ...prev,
          [sessionId]: res.data.data?.logs || []
        }))
      }
    } catch (err) {
      console.error('Failed to load attendance logs', err)
    } finally {
      setLoadingLogs(prev => {
        const next = new Set(prev)
        next.delete(sessionId)
        return next
      })
    }
  }

  const toggleSession = (sessionId: number) => {
    const newExpanded = new Set(expandedSessions)
    if (newExpanded.has(sessionId)) {
      newExpanded.delete(sessionId)
    } else {
      newExpanded.add(sessionId)
      loadAttendanceLogs(sessionId)
    }
    setExpandedSessions(newExpanded)
  }

  return (
    <DashboardLayout
      userRole={user?.role || ''}
      name={user?.name || ''}
      onLogout={() => {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        navigate('/login')
      }}
      title="All Attendance Sessions"
    >
      <Card>
        <CardHeader className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <CalendarClock className="h-5 w-5" />
            {user?.role.toLowerCase() === 'student' ? 'My Attendance Sessions' : 'All Attendance Sessions'}
          </CardTitle>
          <Button variant="outline" size="sm" onClick={loadSessions} disabled={loading}>
            <RefreshCw className="mr-2 h-4 w-4" />
            Refresh
          </Button>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="flex items-center gap-2 text-muted-foreground py-8">
              <Loader2 className="h-4 w-4 animate-spin" />
              Loading sessions...
            </div>
          ) : sessions.length === 0 ? (
            <div className="text-muted-foreground text-sm py-8 text-center">
              No sessions found.
            </div>
          ) : (
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12"></TableHead>
                    <TableHead>Course</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead>Expires</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Scanned</TableHead>
                    <TableHead>Created By</TableHead>
                    <TableHead>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {sessions.map((s) => {
                    const expired = s.is_expired === 1 || new Date(s.expires_at).getTime() <= Date.now()
                    const isExpanded = expandedSessions.has(s.id)
                    const logs = attendanceLogs[s.id] || []
                    const isLoadingLogs = loadingLogs.has(s.id)
                    
                    return (
                      <>
                        <TableRow key={s.id} className="cursor-pointer hover:bg-muted/50">
                          <TableCell>
                            {user?.role.toLowerCase() !== 'student' && (
                              <Button
                                variant="ghost"
                                size="sm"
                                className="h-8 w-8 p-0"
                                onClick={() => toggleSession(s.id)}
                              >
                                {isExpanded ? (
                                  <ChevronDown className="h-4 w-4" />
                                ) : (
                                  <ChevronRight className="h-4 w-4" />
                                )}
                              </Button>
                            )}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <BookOpen className="h-4 w-4 text-muted-foreground" />
                              <div>
                                <div className="font-medium">{s.course_name || `Course #${s.course_id}`}</div>
                                <div className="text-xs text-muted-foreground">ID: {s.course_id}</div>
                              </div>
                            </div>
                          </TableCell>
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
                          <TableCell className="text-sm text-muted-foreground">
                            {s.created_by_email}
                          </TableCell>
                          <TableCell>
                            <Button
                              size="sm"
                              variant="ghost"
                              onClick={(e) => {
                                e.stopPropagation()
                                navigate(`/dashboard/courses/${s.course_id}/attendance-sessions`)
                              }}
                            >
                              <ExternalLink className="mr-2 h-4 w-4" />
                              View Course
                            </Button>
                          </TableCell>
                        </TableRow>
                        {isExpanded && (
                          <TableRow key={`${s.id}-details`}>
                            <TableCell colSpan={8} className="bg-muted/30">
                              <div className="py-4">
                                <div className="flex items-center gap-2 mb-3">
                                  <Users className="h-4 w-4" />
                                  <span className="font-medium">Attendance Records ({logs.length} scanned)</span>
                                </div>
                                {isLoadingLogs ? (
                                  <div className="flex items-center gap-2 text-muted-foreground py-4">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Loading attendance records...
                                  </div>
                                ) : logs.length === 0 ? (
                                  <div className="text-sm text-muted-foreground py-4">
                                    No attendance records yet for this session.
                                  </div>
                                ) : (
                                  <div className="rounded-md border bg-background">
                                    <Table>
                                      <TableHeader>
                                        <TableRow>
                                          <TableHead>Student Name</TableHead>
                                          <TableHead>Email</TableHead>
                                          <TableHead>Scanned At</TableHead>
                                          <TableHead>Status</TableHead>
                                        </TableRow>
                                      </TableHeader>
                                      <TableBody>
                                        {logs.map((log) => (
                                          <TableRow key={log.id}>
                                            <TableCell className="font-medium">{log.student_name}</TableCell>
                                            <TableCell className="text-sm text-muted-foreground">
                                              {log.student_email}
                                            </TableCell>
                                            <TableCell>
                                              {new Date(log.scanned_at).toLocaleString()}
                                            </TableCell>
                                            <TableCell>
                                              <span className="flex items-center gap-1 text-green-600">
                                                <CheckCircle2 className="h-4 w-4" />
                                                Present
                                              </span>
                                            </TableCell>
                                          </TableRow>
                                        ))}
                                      </TableBody>
                                    </Table>
                                  </div>
                                )}
                              </div>
                            </TableCell>
                          </TableRow>
                        )}
                      </>
                    )
                  })}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>
    </DashboardLayout>
  )
}

