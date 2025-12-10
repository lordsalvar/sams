import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import axios from 'axios'
import QRCode from 'qrcode.react'
import { DashboardLayout } from '../components/DashboardLayout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Button } from '../components/ui/button'
import { Loader2, QrCode, RefreshCw, AlertTriangle } from 'lucide-react'

const api = axios.create({ baseURL: '/api' })

interface Session {
  token: string
  expires_at: string
  course_id: number
  course_name?: string
  is_expired?: number
  id?: number
}

interface AttendanceLog {
  id: number
  student_id: number
  student_name: string
  student_email: string
  scanned_at: string
}

export default function AttendanceDisplay() {
  const navigate = useNavigate()
  const { courseId } = useParams<{ courseId: string }>()
  const [user, setUser] = useState<{ role: string; name: string; email?: string } | null>(null)
  const [session, setSession] = useState<Session | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [creating, setCreating] = useState(false)
  const [logs, setLogs] = useState<AttendanceLog[]>([])
  const [logsLoading, setLogsLoading] = useState(false)

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
      loadSession()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [courseId, user?.role])

  const loadSession = async () => {
    if (!courseId || !user) return
    setLoading(true)
    setError('')
    try {
      const res = await api.get('/courses/attendance-session', {
        params: {
          course_id: Number(courseId),
          requested_by_role: user.role,
        },
      })
      if (res.data?.success && res.data.data) {
        setSession(res.data.data)
        await loadLogs(res.data.data.token, res.data.data.id)
      } else {
        setSession(null)
        setLogs([])
      }
    } catch (err: any) {
      console.error(err)
      setError(err?.response?.data?.message || 'Failed to load attendance session')
    } finally {
      setLoading(false)
    }
  }

  const createSession = async () => {
    if (!courseId || !user) return
    setCreating(true)
    setError('')
    try {
      const res = await api.post('/courses/attendance-session', {
        course_id: Number(courseId),
        requested_by_role: user.role,
        requested_by_email: user.email || '',
      })
      if (res.data?.success && res.data.data) {
        setSession(res.data.data)
        await loadLogs(res.data.data.token, res.data.data.id)
      } else {
        setError(res.data?.message || 'Failed to create attendance session')
      }
    } catch (err: any) {
      console.error(err)
      setError(err?.response?.data?.message || 'Failed to create attendance session')
    } finally {
      setCreating(false)
    }
  }

  const loadLogs = async (token?: string, sessionId?: number) => {
    if (!user) return
    if (!token && !sessionId) return
    setLogsLoading(true)
    try {
      const res = await api.get('/courses/attendance-logs', {
        params: {
          requested_by_role: user.role,
          token,
          session_id: sessionId,
        }
      })
      if (res.data?.success) {
        setLogs(res.data.data?.logs || [])
      }
    } catch (err) {
      console.error('Failed to load attendance logs', err)
    } finally {
      setLogsLoading(false)
    }
  }

  const attendanceLink = session
    ? `${window.location.origin}/attendance-scan?token=${session.token}`
    : ''

  const isExpired = session ? (session.is_expired === 1 || new Date(session.expires_at).getTime() <= Date.now()) : false

  return (
    <DashboardLayout
      userRole={user?.role || ''}
      name={user?.name || ''}
      onLogout={() => {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        navigate('/login')
      }}
      title="Attendance QR Display"
    >
      <div className="flex justify-center">
        <Card className="w-full max-w-4xl">
          <CardHeader className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
              <CardTitle className="flex items-center gap-2">
                <QrCode className="h-5 w-5" />
                Attendance QR (Fullscreen)
              </CardTitle>
              <CardDescription>
                Show this QR to students for scanning. Sessions last 15 minutes.
              </CardDescription>
            </div>
            <div className="flex gap-2">
              <Button variant="outline" onClick={() => navigate(-1)}>
                Back
              </Button>
              <Button onClick={loadSession} variant="outline">
                <RefreshCw className="mr-2 h-4 w-4" />
                Refresh
              </Button>
              <Button onClick={createSession} disabled={creating}>
                {creating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <QrCode className="mr-2 h-4 w-4" />}
                Generate New QR
              </Button>
            </div>
          </CardHeader>
          <CardContent className="flex flex-col items-center gap-4">
            {loading ? (
              <div className="flex items-center gap-2 text-muted-foreground">
                <Loader2 className="h-5 w-5 animate-spin" />
                Loading session...
              </div>
            ) : session ? (
              <>
                <div className="flex flex-col items-center gap-2">
                  <QRCode value={attendanceLink} size={360} includeMargin />
                  <div className="text-center text-sm text-muted-foreground">
                    {session.course_name ? `${session.course_name}` : 'Attendance session'}
                  </div>
                  <div className="text-sm">
                    Expires at:{' '}
                    <span className={isExpired ? 'text-destructive font-medium' : 'font-medium'}>
                      {new Date(session.expires_at).toLocaleString()}
                    </span>
                  </div>
                  {isExpired && (
                    <div className="flex items-center gap-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-destructive text-sm">
                      <AlertTriangle className="h-4 w-4" />
                      Session expired. Generate a new QR.
                    </div>
                  )}
                </div>
                <div className="w-full border-t pt-4">
                  <div className="flex items-center justify-between mb-2">
                    <div className="font-medium">
                      Attendance log {logsLoading ? '(loading...)' : `(${logs.length})`}
                    </div>
                    <Button variant="outline" size="sm" onClick={() => loadLogs(session.token, session.id)} disabled={logsLoading}>
                      <RefreshCw className="mr-2 h-4 w-4" />
                      Refresh log
                    </Button>
                  </div>
                  {logs.length === 0 ? (
                    <div className="text-sm text-muted-foreground">No scans recorded yet for this session.</div>
                  ) : (
                    <div className="w-full overflow-auto">
                      <table className="w-full text-sm">
                        <thead className="text-left text-muted-foreground">
                          <tr>
                            <th className="py-2 pr-4">Student</th>
                            <th className="py-2 pr-4">Email</th>
                            <th className="py-2 pr-4">Scanned At</th>
                          </tr>
                        </thead>
                        <tbody>
                          {logs.map((log) => (
                            <tr key={log.id} className="border-t border-border">
                              <td className="py-2 pr-4">{log.student_name}</td>
                              <td className="py-2 pr-4">{log.student_email}</td>
                              <td className="py-2 pr-4">
                                {new Date(log.scanned_at).toLocaleString()}
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              </>
            ) : (
              <div className="flex flex-col items-center gap-2 text-center text-muted-foreground">
                <QrCode className="h-10 w-10 opacity-60" />
                <p>No active attendance session found for this course.</p>
                {error && <p className="text-destructive text-sm">{error}</p>}
                <Button onClick={createSession} disabled={creating}>
                  {creating ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <QrCode className="mr-2 h-4 w-4" />}
                  Generate QR
                </Button>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}

