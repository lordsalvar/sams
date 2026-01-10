import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { DashboardLayout } from '../components/DashboardLayout'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../components/ui/table'
import { Badge } from '../components/ui/badge'
import { Button } from '../components/ui/button'
import { Loader2, Database, RefreshCw, BarChart3, TrendingUp, Users, Calendar } from 'lucide-react'

const api = axios.create({ baseURL: '/api' })

interface AttendanceFeature {
  student_key: number
  student_id: number
  student_name: string
  student_email: string
  course_key: number
  course_code: string
  course_name: string
  day_of_week: number
  day_name: string
  month_name: string
  year: number
  total_attendance_count: number
  number_of_absences: number
  number_of_late: number
  total_sessions: number
  attendance_rate: number
}

interface AttendanceByDay {
  day_of_week: number
  day_name: string
  course_code: string
  course_name: string
  present_count: number
  absent_count: number
  late_count: number
  total_count: number
  attendance_rate: number
}

interface AttendanceSummary {
  course_code: string
  course_name: string
  present_count: number
  absent_count: number
  late_count: number
  total_records: number
  unique_students: number
  attendance_rate: number
}

export default function Analytics() {
  const navigate = useNavigate()
  const [user, setUser] = useState<{ role: string; name: string; email?: string } | null>(null)
  const [loading, setLoading] = useState(true)
  const [refreshing, setRefreshing] = useState(false)
  const [activeTab, setActiveTab] = useState<'features' | 'byDay' | 'summary'>('features')
  
  // Data states
  const [features, setFeatures] = useState<AttendanceFeature[]>([])
  const [attendanceByDay, setAttendanceByDay] = useState<AttendanceByDay[]>([])
  const [summary, setSummary] = useState<AttendanceSummary[]>([])

  useEffect(() => {
    const storedUser = localStorage.getItem('user')
    if (!storedUser) {
      navigate('/login')
      return
    }
    const parsed = JSON.parse(storedUser)
    setUser(parsed)
    
    // Only admin and instructor can access
    if (!['admin', 'instructor'].includes(parsed.role.toLowerCase())) {
      navigate('/dashboard')
      return
    }
    
    loadData()
  }, [navigate])

  const loadData = async () => {
    setLoading(true)
    try {
      await Promise.all([
        loadFeatures(),
        loadAttendanceByDay(),
        loadSummary()
      ])
    } catch (err) {
      console.error('Failed to load data', err)
    } finally {
      setLoading(false)
    }
  }

  const loadFeatures = async () => {
    try {
      const res = await api.get('/analytics/features', {
        params: { requested_by_role: user?.role }
      })
      if (res.data?.success) {
        setFeatures(res.data.data || [])
      }
    } catch (err) {
      console.error('Failed to load features', err)
    }
  }

  const loadAttendanceByDay = async () => {
    try {
      const res = await api.get('/analytics/by-day', {
        params: { requested_by_role: user?.role }
      })
      if (res.data?.success) {
        setAttendanceByDay(res.data.data || [])
      }
    } catch (err) {
      console.error('Failed to load attendance by day', err)
    }
  }

  const loadSummary = async () => {
    try {
      const res = await api.get('/analytics/summary', {
        params: { requested_by_role: user?.role }
      })
      if (res.data?.success) {
        setSummary(res.data.data || [])
      }
    } catch (err) {
      console.error('Failed to load summary', err)
    }
  }

  const handleRefresh = async () => {
    setRefreshing(true)
    try {
      const res = await api.post('/analytics/refresh', {
        requested_by_role: user?.role
      })
      if (res.data?.success) {
        await loadData()
        alert('Analytics data refreshed successfully!')
      } else {
        alert(res.data?.message || 'Failed to refresh analytics data')
      }
    } catch (err: any) {
      console.error('Failed to refresh', err)
      alert(err?.response?.data?.message || 'Failed to refresh analytics data')
    } finally {
      setRefreshing(false)
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
      title="Analytics"
    >
      <div className="flex flex-col gap-4">
        {/* Header Actions */}
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <Database className="h-5 w-5" />
            <h2 className="text-xl font-semibold">Analytics</h2>
          </div>
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={handleRefresh}
              disabled={refreshing}
            >
              {refreshing ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <RefreshCw className="mr-2 h-4 w-4" />
              )}
              Refresh Data
            </Button>
          </div>
        </div>

        {/* Tabs */}
        <div className="flex gap-2 border-b">
          <Button
            variant={activeTab === 'features' ? 'default' : 'ghost'}
            onClick={() => setActiveTab('features')}
            className="rounded-b-none"
          >
            <BarChart3 className="mr-2 h-4 w-4" />
            Student Features
          </Button>
          <Button
            variant={activeTab === 'byDay' ? 'default' : 'ghost'}
            onClick={() => setActiveTab('byDay')}
            className="rounded-b-none"
          >
            <Calendar className="mr-2 h-4 w-4" />
            By Day of Week
          </Button>
          <Button
            variant={activeTab === 'summary' ? 'default' : 'ghost'}
            onClick={() => setActiveTab('summary')}
            className="rounded-b-none"
          >
            <TrendingUp className="mr-2 h-4 w-4" />
            Summary
          </Button>
        </div>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            <span className="ml-2 text-muted-foreground">Loading analytics data...</span>
          </div>
        ) : (
          <>
            {/* Student Features Tab */}
            {activeTab === 'features' && (
              <Card>
                <CardHeader>
                  <CardTitle>Student Attendance Features</CardTitle>
                  <CardDescription>
                    Detailed attendance features for data mining: total attendance count, absences, class, and day of week
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {features.length === 0 ? (
                    <div className="text-sm text-muted-foreground py-8 text-center">
                      No attendance features data available. Click "Refresh Data" to populate.
                    </div>
                  ) : (
                    <div className="rounded-md border overflow-auto max-h-[600px]">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>Student</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Day</TableHead>
                            <TableHead>Present</TableHead>
                            <TableHead>Absent</TableHead>
                            <TableHead>Late</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Rate</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {features.map((f, idx) => (
                            <TableRow key={`${f.student_key}-${f.course_key}-${f.day_of_week}-${idx}`}>
                              <TableCell className="font-medium">{f.student_name}</TableCell>
                              <TableCell>
                                <div>
                                  <div className="font-medium">{f.course_code}</div>
                                  <div className="text-xs text-muted-foreground">{f.course_name}</div>
                                </div>
                              </TableCell>
                              <TableCell>{f.day_name}</TableCell>
                              <TableCell>{f.total_attendance_count}</TableCell>
                              <TableCell>{f.number_of_absences}</TableCell>
                              <TableCell>{f.number_of_late}</TableCell>
                              <TableCell>{f.total_sessions}</TableCell>
                              <TableCell>
                                <Badge variant={f.attendance_rate >= 80 ? 'default' : f.attendance_rate >= 60 ? 'secondary' : 'destructive'}>
                                  {f.attendance_rate.toFixed(1)}%
                                </Badge>
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {/* By Day of Week Tab */}
            {activeTab === 'byDay' && (
              <Card>
                <CardHeader>
                  <CardTitle>Attendance by Day of Week</CardTitle>
                  <CardDescription>
                    Attendance statistics grouped by day of week for each course
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {attendanceByDay.length === 0 ? (
                    <div className="text-sm text-muted-foreground py-8 text-center">
                      No attendance by day data available.
                    </div>
                  ) : (
                    <div className="rounded-md border overflow-auto max-h-[600px]">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>Day</TableHead>
                            <TableHead>Course</TableHead>
                            <TableHead>Present</TableHead>
                            <TableHead>Absent</TableHead>
                            <TableHead>Late</TableHead>
                            <TableHead>Total</TableHead>
                            <TableHead>Rate</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {attendanceByDay.map((d, idx) => (
                            <TableRow key={`${d.day_of_week}-${d.course_code}-${idx}`}>
                              <TableCell className="font-medium">{d.day_name}</TableCell>
                              <TableCell>
                                <div>
                                  <div className="font-medium">{d.course_code}</div>
                                  <div className="text-xs text-muted-foreground">{d.course_name}</div>
                                </div>
                              </TableCell>
                              <TableCell>{d.present_count}</TableCell>
                              <TableCell>{d.absent_count}</TableCell>
                              <TableCell>{d.late_count}</TableCell>
                              <TableCell>{d.total_count}</TableCell>
                              <TableCell>
                                <Badge variant={d.attendance_rate >= 80 ? 'default' : d.attendance_rate >= 60 ? 'secondary' : 'destructive'}>
                                  {d.attendance_rate.toFixed(1)}%
                                </Badge>
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {/* Summary Tab */}
            {activeTab === 'summary' && (
              <div className="grid gap-4">
                {summary.length === 0 ? (
                  <Card>
                    <CardContent className="py-8 text-center">
                      <div className="text-sm text-muted-foreground">
                        No summary data available.
                      </div>
                    </CardContent>
                  </Card>
                ) : (
                  summary.map((s) => (
                    <Card key={s.course_code}>
                      <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                          <Users className="h-5 w-5" />
                          {s.course_code} - {s.course_name}
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                          <div>
                            <div className="text-2xl font-bold text-green-600">{s.present_count}</div>
                            <div className="text-sm text-muted-foreground">Present</div>
                          </div>
                          <div>
                            <div className="text-2xl font-bold text-red-600">{s.absent_count}</div>
                            <div className="text-sm text-muted-foreground">Absent</div>
                          </div>
                          <div>
                            <div className="text-2xl font-bold text-yellow-600">{s.late_count}</div>
                            <div className="text-sm text-muted-foreground">Late</div>
                          </div>
                          <div>
                            <div className="text-2xl font-bold">{s.attendance_rate.toFixed(1)}%</div>
                            <div className="text-sm text-muted-foreground">Attendance Rate</div>
                          </div>
                        </div>
                        <div className="mt-4 pt-4 border-t">
                          <div className="text-sm text-muted-foreground">
                            Total Records: {s.total_records} · Unique Students: {s.unique_students}
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  ))
                )}
              </div>
            )}

          </>
        )}
      </div>
    </DashboardLayout>
  )
}
