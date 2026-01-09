import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import axios from 'axios'
import { DashboardLayout } from '../components/DashboardLayout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Button } from '../components/ui/button'
import { AlertTriangle, CheckCircle2, Loader2, QrCode } from 'lucide-react'

const api = axios.create({ baseURL: '/api' })

type User = { role: string; name: string; email?: string }

export default function AttendanceScan() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') || ''

  const [user, setUser] = useState<User | null>(null)
  const [status, setStatus] = useState<'idle' | 'success' | 'error'>('idle')
  const [message, setMessage] = useState<string>('')
  const [courseName, setCourseName] = useState<string>('')
  const [loading, setLoading] = useState<boolean>(true)

  useEffect(() => {
    const storedUser = localStorage.getItem('user')
    if (!storedUser) {
      navigate('/login')
      return
    }

    const parsedUser: User = JSON.parse(storedUser)
    setUser(parsedUser)

    if (!parsedUser.role || parsedUser.role.toLowerCase() !== 'student') {
      navigate('/dashboard')
      return
    }

    if (!token) {
      setStatus('error')
      setMessage('Missing attendance token. Please scan a valid QR code.')
      setLoading(false)
      return
    }

    submitAttendance(parsedUser)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token, navigate])

  const submitAttendance = async (currentUser: User) => {
    setLoading(true)
    try {
      const res = await api.post('/courses/attendance-scan', {
        token,
        student_email: currentUser.email,
        requested_by_role: currentUser.role,
      })

      if (res.data?.success) {
        setStatus('success')
        setMessage(res.data?.message || 'Attendance recorded successfully.')
        setCourseName(res.data?.data?.course_name || '')
      } else {
        setStatus('error')
        setMessage(res.data?.message || 'Failed to record attendance.')
      }
    } catch (err: any) {
      console.error(err)
      setStatus('error')
      setMessage(err?.response?.data?.message || 'Failed to record attendance.')
    } finally {
      setLoading(false)
    }
  }

  if (!user) {
    return (
      <div className="flex min-h-screen items-center justify-center">
        <div className="text-muted-foreground">Loading...</div>
      </div>
    )
  }

  return (
    <DashboardLayout
      userRole={user.role}
      name={user.name}
      onLogout={() => {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        navigate('/login')
      }}
      title="Attendance Scan"
    >
      <div className="flex justify-center">
        <Card className="w-full max-w-xl">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <QrCode className="h-5 w-5" />
              Attendance Check-In
            </CardTitle>
            <CardDescription>
              This page records your attendance for the course linked to the scanned QR code.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {loading ? (
              <div className="flex items-center gap-2 text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Recording your attendance...
              </div>
            ) : status === 'success' ? (
              <div className="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 p-3 text-green-800">
                <CheckCircle2 className="h-5 w-5" />
                <div>
                  <p className="font-medium">Attendance recorded</p>
                  <p className="text-sm text-green-700">
                    {courseName ? `Course: ${courseName}` : 'Successfully checked in.'}
                  </p>
                </div>
              </div>
            ) : (
              <div className="flex items-center gap-3 rounded-lg border border-destructive/30 bg-destructive/10 p-3 text-destructive">
                <AlertTriangle className="h-5 w-5" />
                <div>
                  <p className="font-medium">Unable to record attendance</p>
                  <p className="text-sm text-muted-foreground">{message}</p>
                </div>
              </div>
            )}

            <div className="flex flex-wrap gap-2">
              <Button variant="outline" onClick={() => navigate('/dashboard/student')}>
                Go to Dashboard
              </Button>
              <Button variant="ghost" onClick={() => navigate(-1)}>
                Go Back
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}

