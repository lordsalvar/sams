import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import axios from 'axios'
import { ArrowLeft, User, Mail, BookOpen, Users, UserMinus, UserPlus, Pencil, Trash2, Check, ChevronsUpDown, QrCode, Loader2, Copy, Maximize2, CalendarClock } from 'lucide-react'
import { DashboardLayout } from '../components/DashboardLayout'
import { Button } from '../components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '../components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '../components/ui/dialog'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '../components/ui/command'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '../components/ui/popover'
import { Input } from '../components/ui/input'
import { Label } from '../components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '../components/ui/select'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Badge } from '../components/ui/badge'
import { cn } from '../lib/utils'
import QRCode from 'qrcode.react'
import { Link } from 'react-router-dom'

interface CourseDetail {
  id: number
  name: string
  code: string
  instructor_email: string
  instructor_name?: string
  created_at: string
  updated_at: string
}

interface EnrolledStudent {
  id: number
  student_id: number
  student_name: string
  student_email: string
  enrolled_at: string
}

interface Instructor {
  id: number
  name: string
  email: string
}

interface Student {
  id: number
  name: string
  email: string
}

const api = axios.create({ baseURL: '/api' })

export default function CourseDetail() {
  const navigate = useNavigate()
  const { courseId } = useParams<{ courseId: string }>()
  const [user, setUser] = useState<{ role: string; name: string; email?: string } | null>(null)
  const [course, setCourse] = useState<CourseDetail | null>(null)
  const [students, setStudents] = useState<EnrolledStudent[]>([])
  const [loading, setLoading] = useState(true)
  const [enrollDialogOpen, setEnrollDialogOpen] = useState(false)
  const [enrollEmail, setEnrollEmail] = useState('')
  const [studentComboOpen, setStudentComboOpen] = useState(false)
  const [editDialogOpen, setEditDialogOpen] = useState(false)
  const [instructors, setInstructors] = useState<Instructor[]>([])
  const [availableStudents, setAvailableStudents] = useState<Student[]>([])
  const [allStudentsCount, setAllStudentsCount] = useState(0)
  const [loadingStudents, setLoadingStudents] = useState(false)
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    instructor_email: '',
  })
  const [attendanceSession, setAttendanceSession] = useState<{ token: string; expires_at: string; is_expired?: number } | null>(null)
  const [qrGenerating, setQrGenerating] = useState(false)
  const [qrError, setQrError] = useState('')

  useEffect(() => {
    const userStr = localStorage.getItem('user')
    if (!userStr) {
      navigate('/login')
      return
    }

    const userData = JSON.parse(userStr)
    setUser(userData)

    // All authenticated users can view course details (students only for enrolled courses)
    loadCourseDetail()
  }, [navigate, courseId])

  useEffect(() => {
    if (course && user) {
      loadAttendanceSession()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [course?.id, user?.role])

  const loadCourseDetail = async () => {
    setLoading(true)
    try {
      const params: any = { 
        id: courseId,
        requested_by_role: user?.role || ''
      }
      
      // Add student_email for students
      if (user?.role.toLowerCase() === 'student' && user?.email) {
        params.student_email = user.email
      }
      
      const res = await api.get('/courses', { params })
      
      if (res.data?.success) {
        setCourse(res.data.data.course)
        setStudents(res.data.data.students || [])
      } else {
        alert(res.data?.message || 'Failed to load course details')
      }
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to load course details')
    } finally {
      setLoading(false)
    }
  }

  const loadInstructors = async () => {
    if (!user) return
    try {
      const res = await api.get('/courses/instructors', {
        params: { requested_by_role: user.role },
      })
      if (res.data?.success) {
        setInstructors(res.data.data ?? [])
      }
    } catch (err) {
      console.error(err)
    }
  }

  const loadAvailableStudents = async () => {
    if (!user) return
    setLoadingStudents(true)
    try {
      const res = await api.get('/courses/students', {
        params: { requested_by_role: user.role },
      })
      if (res.data?.success) {
        const allStudents = res.data.data ?? []
        setAllStudentsCount(allStudents.length)
        // Filter out students who are already enrolled in this course
        const enrolledEmails = new Set(students.map(s => s.student_email))
        const availableOnly = allStudents.filter((student: Student) => !enrolledEmails.has(student.email))
        setAvailableStudents(availableOnly)
      }
    } catch (err) {
      console.error(err)
    } finally {
      setLoadingStudents(false)
    }
  }

  const handleEditCourse = async () => {
    if (!course) return
    await loadInstructors()
    setFormData({
      name: course.name,
      code: course.code,
      instructor_email: course.instructor_email,
    })
    setEditDialogOpen(true)
  }

  const handleUpdateCourse = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!user || !course) return
    
    try {
      await api.put('/courses', {
        id: course.id,
        name: formData.name,
        code: formData.code,
        instructor_email: formData.instructor_email,
        requested_by_role: user.role,
      })
      await loadCourseDetail()
      setEditDialogOpen(false)
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to update course')
    }
  }

  const handleDeleteCourse = async () => {
    if (!course || !confirm(`Delete course "${course.name}"? This action cannot be undone.`)) return
    
    try {
      await api.delete('/courses', { params: { id: course.id } })
      navigate('/dashboard/courses')
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to delete course')
    }
  }

  const handleOpenEnrollDialog = async () => {
    await loadAvailableStudents()
    setEnrollEmail('')
    setEnrollDialogOpen(true)
  }

  const handleGenerateQr = async () => {
    if (!course || !user) return
    setQrError('')
    setQrGenerating(true)
    try {
      const res = await api.post('/courses/attendance-session', {
        course_id: course.id,
        requested_by_role: user.role,
        requested_by_email: user.email || '',
      })
      if (res.data?.success && res.data.data) {
        setAttendanceSession(res.data.data)
      } else {
        setQrError(res.data?.message || 'Failed to generate attendance QR')
      }
    } catch (err: any) {
      console.error(err)
      setQrError(err?.response?.data?.message || 'Failed to generate attendance QR')
    } finally {
      setQrGenerating(false)
    }
  }

  const attendanceLink = attendanceSession
    ? `${window.location.origin}/attendance-scan?token=${attendanceSession.token}`
    : ''
  const attendanceExpired = attendanceSession
    ? (attendanceSession.is_expired === 1 || new Date(attendanceSession.expires_at).getTime() <= Date.now())
    : false

  const loadAttendanceSession = async () => {
    if (!course || !user) return
    try {
      const res = await api.get('/courses/attendance-session', {
        params: {
          course_id: course.id,
          requested_by_role: user.role,
        }
      })
      if (res.data?.success && res.data.data) {
        setAttendanceSession(res.data.data)
        return
      }
    } catch (err) {
      console.error('Failed to load attendance session', err)
    }
  }

  const handleEnrollStudent = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!course || !user || !enrollEmail) return
    
    try {
      await api.post('/courses/enroll', {
        course_id: course.id,
        student_email: enrollEmail.trim(),
        requested_by_role: user.role,
      })
      setEnrollDialogOpen(false)
      setEnrollEmail('')
      await loadCourseDetail()
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to enroll student')
    }
  }

  const handleUnenroll = async (enrollmentId: number, studentName: string) => {
    if (!confirm(`Remove ${studentName} from this course?`)) return
    if (!user) return
    
    try {
      await api.delete('/courses/unenroll', {
        params: { 
          enrollment_id: enrollmentId,
          requested_by_role: user.role
        }
      })
      await loadCourseDetail()
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to unenroll student')
    }
  }

  const handleLogout = () => {
    localStorage.removeItem('user')
    localStorage.removeItem('token')
    navigate('/login')
  }

  const handleBack = () => {
    navigate('/dashboard/courses')
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
      onLogout={handleLogout}
      title={course?.name || 'Course Details'}
    >
      <div className="space-y-6">
        <div className="space-y-4">
          <Button
            variant="ghost"
            onClick={handleBack}
            className="-ml-2"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Back to Courses
          </Button>
          
          <div className="flex items-start justify-between">
            <div className="space-y-1">
              <h1 className="text-3xl font-bold tracking-tight">
                {loading ? 'Loading...' : course?.name}
              </h1>
              <p className="text-muted-foreground">
                {user?.role.toLowerCase() === 'student' 
                  ? 'View course details and attendance sessions.'
                  : 'View course details and manage enrolled students.'}
              </p>
            </div>
            
            {!loading && course && (
              <div className="flex gap-2">
                {['admin', 'instructor'].includes(user?.role.toLowerCase() || '') && (
                  <>
                    <Button
                      variant="outline"
                      onClick={handleOpenEnrollDialog}
                    >
                      <UserPlus className="mr-2 h-4 w-4" />
                      Enroll Student
                    </Button>
                    <Button
                      onClick={handleGenerateQr}
                      variant="default"
                      disabled={qrGenerating}
                    >
                      {qrGenerating ? (
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      ) : (
                        <QrCode className="mr-2 h-4 w-4" />
                      )}
                      Generate Attendance QR
                    </Button>
                  </>
                )}
                {user?.role.toLowerCase() === 'admin' && (
                  <>
                    <Button
                      variant="outline"
                      onClick={handleEditCourse}
                    >
                      <Pencil className="mr-2 h-4 w-4" />
                      Edit Course
                    </Button>
                    <Button
                      variant="destructive"
                      onClick={handleDeleteCourse}
                    >
                      <Trash2 className="mr-2 h-4 w-4" />
                      Delete
                    </Button>
                  </>
                )}
              </div>
            )}
          </div>
        </div>

        {loading ? (
          <div className="flex items-center justify-center py-12">
            <div className="text-muted-foreground">Loading course details...</div>
          </div>
        ) : !course ? (
          <Card>
            <CardContent className="py-12 text-center">
              <p className="text-muted-foreground">Course not found</p>
            </CardContent>
          </Card>
        ) : (
          <>
            {/* Course Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <BookOpen className="h-5 w-5" />
                  Course Information
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Course Name</p>
                    <p className="text-lg font-semibold">{course.name}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Course Code</p>
                    <Badge variant="secondary" className="text-base mt-1">
                      {course.code}
                    </Badge>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Instructor</p>
                    <div className="flex items-center gap-2 mt-1">
                      <User className="h-4 w-4 text-muted-foreground" />
                      <span>{course.instructor_name || course.instructor_email}</span>
                    </div>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Instructor Email</p>
                    <div className="flex items-center gap-2 mt-1">
                      <Mail className="h-4 w-4 text-muted-foreground" />
                      <span className="text-sm">{course.instructor_email}</span>
                    </div>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Created</p>
                    <p className="text-sm">
                      {new Date(course.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                      })}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Total Students</p>
                    <div className="flex items-center gap-2 mt-1">
                      <Users className="h-4 w-4 text-muted-foreground" />
                      <span className="text-lg font-semibold">{students.length}</span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            {['admin', 'instructor'].includes(user.role.toLowerCase()) && (
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <QrCode className="h-5 w-5" />
                    Attendance QR
                  </CardTitle>
                  <CardDescription>
                    Generate a time-bound QR that students can scan to record attendance.
                  </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                  <div className="space-y-2">
                    <p className="text-sm text-muted-foreground">
                      Click "Generate Attendance QR" to create a 15-minute session. Share the QR or link with students.
                    </p>
                    {qrError && (
                      <p className="text-sm text-destructive">{qrError}</p>
                    )}
                    {attendanceSession && (
                      <div className="space-y-1 text-sm">
                        <div className="flex items-center gap-2">
                          <span className="font-medium">Expires at:</span>
                          <span>
                            {new Date(attendanceSession.expires_at).toLocaleString()}
                          </span>
                        </div>
                        {attendanceExpired && (
                          <div className="text-sm text-destructive">
                            Session expired. Generate a new QR.
                          </div>
                        )}
                        <div className="flex items-center gap-2 break-all">
                          <span className="font-medium">Link:</span>
                          <span className="text-muted-foreground">{attendanceLink}</span>
                          <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            onClick={() => attendanceLink && navigator.clipboard.writeText(attendanceLink)}
                            aria-label="Copy attendance link"
                          >
                            <Copy className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    )}
                  </div>
                  {attendanceSession ? (
                    <div className="flex flex-col items-center gap-2">
                      <QRCode value={attendanceLink} size={180} includeMargin />
                      <p className="text-xs text-muted-foreground">
                        Students scan this to record attendance.
                      </p>
                      {attendanceExpired && (
                        <p className="text-xs text-destructive">Expired</p>
                      )}
                      <Button asChild variant="outline" className="mt-2">
                        <Link to={`/dashboard/courses/${course.id}/attendance-display`}>
                          <Maximize2 className="mr-2 h-4 w-4" />
                          Open Fullscreen QR
                        </Link>
                      </Button>
                      <Button asChild variant="ghost" className="mt-1">
                        <Link to={`/dashboard/courses/${course.id}/attendance-sessions`}>
                          <CalendarClock className="mr-2 h-4 w-4" />
                          View Sessions/Logs
                        </Link>
                      </Button>
                      <Button asChild variant="ghost" className="mt-1">
                        <Link to={`/dashboard/courses/${course.id}/attendance-analytics`}>
                          <CalendarClock className="mr-2 h-4 w-4" />
                          Analytics
                        </Link>
                      </Button>
                    </div>
                  ) : (
                    <div className="text-sm text-muted-foreground">
                      Generate a session to display the QR.
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {/* Enrolled Students - Show to all roles, but hide actions for students */}
            <Card>
              <CardHeader>
                <CardTitle>Enrolled Students</CardTitle>
                <CardDescription>
                  Students currently enrolled in {course.name}
                </CardDescription>
              </CardHeader>
              <CardContent>
                {students.length === 0 ? (
                  <div className="flex flex-col items-center justify-center py-12 text-center">
                    <Users className="h-12 w-12 text-muted-foreground/50 mb-4" />
                    <p className="text-muted-foreground">No students enrolled yet</p>
                    {user?.role.toLowerCase() === 'admin' && (
                      <Button
                        variant="outline"
                        className="mt-4"
                        onClick={handleOpenEnrollDialog}
                      >
                        <UserPlus className="mr-2 h-4 w-4" />
                        Enroll First Student
                      </Button>
                    )}
                  </div>
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Student ID</TableHead>
                        <TableHead>Name</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Enrolled Date</TableHead>
                        {['admin', 'instructor'].includes(user?.role.toLowerCase() || '') && (
                          <TableHead className="text-right">Actions</TableHead>
                        )}
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {students.map((student) => (
                        <TableRow key={student.id}>
                          <TableCell className="font-medium">
                            {student.student_id}
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <User className="h-4 w-4 text-muted-foreground" />
                              {student.student_name}
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <Mail className="h-4 w-4 text-muted-foreground" />
                              <span className="text-sm">{student.student_email}</span>
                            </div>
                          </TableCell>
                          <TableCell>
                            {new Date(student.enrolled_at).toLocaleDateString('en-US', {
                              year: 'numeric',
                              month: 'short',
                              day: 'numeric',
                            })}
                          </TableCell>
                          {['admin', 'instructor'].includes(user?.role.toLowerCase() || '') && (
                            <TableCell className="text-right">
                              {user.role.toLowerCase() === 'admin' && (
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  onClick={() => handleUnenroll(student.id, student.student_name)}
                                  className="text-destructive hover:text-destructive"
                                >
                                  <UserMinus className="mr-2 h-4 w-4" />
                                  Unenroll
                                </Button>
                              )}
                            </TableCell>
                          )}
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>
          </>
        )}
      </div>

      {/* Edit Course Dialog */}
      <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
        <DialogContent className="sm:max-w-[480px]">
          <DialogHeader>
            <DialogTitle>Edit Course</DialogTitle>
            <DialogDescription>
              Update the course information below.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdateCourse} className="space-y-4">
            <div className="grid gap-2">
              <Label htmlFor="name">Course Name</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="code">Course Code</Label>
              <Input
                id="code"
                value={formData.code}
                onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                required
              />
            </div>
            <div className="grid gap-2">
              <Label htmlFor="instructor_email">Instructor</Label>
              {instructors.length === 0 ? (
                <div className="text-sm text-muted-foreground">
                  Loading instructors...
                </div>
              ) : (
                <Select
                  value={formData.instructor_email}
                  onValueChange={(value) => setFormData({ ...formData, instructor_email: value })}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select an instructor" />
                  </SelectTrigger>
                  <SelectContent>
                    {instructors.map((inst) => (
                      <SelectItem key={inst.id} value={inst.email}>
                        {inst.name} ({inst.email})
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit">Update Course</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Enroll Student Dialog */}
      <Dialog open={enrollDialogOpen} onOpenChange={setEnrollDialogOpen}>
        <DialogContent className="sm:max-w-[420px]">
          <DialogHeader>
            <DialogTitle>Enroll Student</DialogTitle>
            <DialogDescription>
              Select a student to enroll in {course?.name || 'this course'}.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleEnrollStudent} className="space-y-4">
            <div className="grid gap-2">
              <Label>Select Student</Label>
              {loadingStudents ? (
                <div className="text-sm text-muted-foreground py-2">Loading students...</div>
              ) : availableStudents.length === 0 ? (
                <div className="text-sm text-muted-foreground py-2">
                  {allStudentsCount === 0 
                    ? 'No students found. Please create student users first.'
                    : 'All students are already enrolled in this course.'}
                </div>
              ) : (
                <Popover open={studentComboOpen} onOpenChange={setStudentComboOpen}>
                  <PopoverTrigger asChild>
                    <Button
                      variant="outline"
                      role="combobox"
                      aria-expanded={studentComboOpen}
                      className="w-full justify-between"
                    >
                      {enrollEmail
                        ? availableStudents.find((student) => student.email === enrollEmail)?.name + 
                          " (" + availableStudents.find((student) => student.email === enrollEmail)?.email + ")"
                        : "Search and select a student..."}
                      <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent className="w-[380px] p-0">
                    <Command>
                      <CommandInput placeholder="Search students by name or email..." />
                      <CommandList>
                        <CommandEmpty>No student found.</CommandEmpty>
                        <CommandGroup>
                          {availableStudents.map((student) => (
                            <CommandItem
                              key={student.id}
                              value={`${student.name} ${student.email}`}
                              onSelect={() => {
                                setEnrollEmail(student.email)
                                setStudentComboOpen(false)
                              }}
                            >
                              <Check
                                className={cn(
                                  "mr-2 h-4 w-4",
                                  enrollEmail === student.email ? "opacity-100" : "opacity-0"
                                )}
                              />
                              <div className="flex flex-col">
                                <span className="font-medium">{student.name}</span>
                                <span className="text-sm text-muted-foreground">{student.email}</span>
                              </div>
                            </CommandItem>
                          ))}
                        </CommandGroup>
                      </CommandList>
                    </Command>
                  </PopoverContent>
                </Popover>
              )}
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEnrollDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={!enrollEmail || loadingStudents || availableStudents.length === 0}>
                Enroll Student
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </DashboardLayout>
  )
}

