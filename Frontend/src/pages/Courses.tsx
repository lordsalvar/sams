import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { Plus, MoreHorizontal, Pencil, Trash2, BookOpen, User, UserPlus, Eye, Check, ChevronsUpDown } from 'lucide-react'
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '../components/ui/dropdown-menu'
import { Input } from '../components/ui/input'
import { Label } from '../components/ui/label'
import { Badge } from '../components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '../components/ui/select'
import { cn } from '../lib/utils'

interface Course {
  id: number
  name: string
  code: string
  instructor_email: string
  enrollment_count?: number
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

export default function Courses() {
  const navigate = useNavigate()
  const [user, setUser] = useState<{ role: string; name: string } | null>(null)
  const [courses, setCourses] = useState<Course[]>([])
  const [loading, setLoading] = useState(true)
  const [isDialogOpen, setIsDialogOpen] = useState(false)
  const [editingCourse, setEditingCourse] = useState<Course | null>(null)
  const [instructors, setInstructors] = useState<Instructor[]>([])
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    instructor_email: '',
  })
  const [enrollDialogOpen, setEnrollDialogOpen] = useState(false)
  const [enrollCourse, setEnrollCourse] = useState<Course | null>(null)
  const [enrollEmail, setEnrollEmail] = useState('')
  const [studentComboOpen, setStudentComboOpen] = useState(false)
  const [availableStudents, setAvailableStudents] = useState<Student[]>([])
  const [allStudentsCount, setAllStudentsCount] = useState(0)
  const [loadingStudents, setLoadingStudents] = useState(false)

  useEffect(() => {
    const userStr = localStorage.getItem('user')
    if (!userStr) {
      navigate('/login')
      return
    }

    const userData = JSON.parse(userStr)
    setUser(userData)

    // Only admins and instructors can view courses
    if (!['admin', 'instructor'].includes(userData.role.toLowerCase())) {
      navigate('/dashboard')
      return
    }

    loadCourses(userData)
    if (userData.role.toLowerCase() === 'admin') {
      loadInstructors(userData.role)
    }
  }, [navigate])

  // Reload available students when enroll dialog opens
  useEffect(() => {
    if (enrollDialogOpen && enrollCourse && user) {
      loadAvailableStudents()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enrollDialogOpen])

  const loadCourses = async (userData?: { role: string; email?: string; name?: string }) => {
    setLoading(true)
    try {
      // Get userData from parameter or localStorage
      if (!userData) {
        const userStr = localStorage.getItem('user')
        userData = userStr ? JSON.parse(userStr) : null
      }
      
      const params: any = {}
      
      // If instructor, filter by their email
      if (userData?.role.toLowerCase() === 'instructor' && userData?.email) {
        params.requested_by_role = userData.role
        params.instructor_email = userData.email
      }
      
      const res = await api.get('/courses', { params })
      if (res.data?.success) {
        setCourses(res.data.data ?? [])
      } else {
        alert(res.data?.message || 'Failed to load courses')
      }
    } catch (err) {
      console.error(err)
      alert('Failed to load courses')
    } finally {
      setLoading(false)
    }
  }

  const loadInstructors = async (role: string) => {
    try {
      // use query param fallback so it works even when pretty route is not available
      const res = await api.get('/courses', {
        params: { list: 'instructors', requested_by_role: role },
      })
      if (res.data?.success) {
        setInstructors(res.data.data ?? [])
      } else {
        alert(res.data?.message || 'Failed to load instructors')
      }
    } catch (err: any) {
      console.error('Error loading instructors:', err)
      console.error('Response data:', err?.response?.data)
      const errorMessage = err?.response?.data?.message || err?.message || 'Failed to load instructors'
      alert(`Failed to load instructors: ${errorMessage}`)
    }
  }

  const loadAvailableStudents = async (course?: Course | null) => {
    const targetCourse = course || enrollCourse
    if (!user || !targetCourse) return
    setLoadingStudents(true)
    try {
      // Load all students
      const res = await api.get('/courses', {
        params: { list: 'students', requested_by_role: user.role },
      })
      if (res.data?.success) {
        const allStudents = res.data.data ?? []
        setAllStudentsCount(allStudents.length)
        
        // Get currently enrolled students for this specific course
        try {
          const courseDetailRes = await api.get('/courses', {
            params: { id: targetCourse.id }
          })
          
          if (courseDetailRes.data?.success && courseDetailRes.data.data) {
            // Handle both possible data structures
            const courseData = courseDetailRes.data.data
            const students = courseData.students || courseData.data?.students || []
            const enrolledEmails = new Set(
              students.map((s: any) => s.student_email || s.email)
            )
            const availableOnly = allStudents.filter((student: Student) => !enrolledEmails.has(student.email))
            setAvailableStudents(availableOnly)
          } else {
            // If course detail fetch fails, show all students
            setAvailableStudents(allStudents)
          }
        } catch (courseErr) {
          console.error('Failed to load course details for enrollment:', courseErr)
          // If course detail fetch fails, show all students
          setAvailableStudents(allStudents)
        }
      } else {
        setAvailableStudents([])
        setAllStudentsCount(0)
      }
    } catch (err) {
      console.error('Failed to load students:', err)
      setAvailableStudents([])
      setAllStudentsCount(0)
    } finally {
      setLoadingStudents(false)
    }
  }

  const handleLogout = () => {
    localStorage.removeItem('user')
    localStorage.removeItem('token')
    navigate('/login')
  }

  const handleAddCourse = async () => {
    setEditingCourse(null)
    // Reload instructors to get the latest list
    if (user) {
      await loadInstructors(user.role)
    }
    setFormData({
      name: '',
      code: '',
      instructor_email: instructors[0]?.email ?? '',
    })
    setIsDialogOpen(true)
  }

  const handleEditCourse = async (course: Course) => {
    setEditingCourse(course)
    // Reload instructors to get the latest list
    if (user) {
      await loadInstructors(user.role)
    }
    setFormData({
      name: course.name,
      code: course.code,
      instructor_email: course.instructor_email,
    })
    setIsDialogOpen(true)
  }

  const handleDeleteCourse = async (courseId: number) => {
    if (!confirm('Delete this course?')) return
    try {
      await api.delete('/courses', { params: { id: courseId } })
      await loadCourses()
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to delete course')
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!user) return
    if (!formData.instructor_email) {
      alert('Please select an instructor')
      return
    }
    try {
      if (editingCourse) {
        await api.put('/courses', {
          id: editingCourse.id,
          name: formData.name,
          code: formData.code,
          instructor_email: formData.instructor_email,
          requested_by_role: user.role,
        })
      } else {
        await api.post('/courses', {
          name: formData.name,
          code: formData.code,
          instructor_email: formData.instructor_email,
          requested_by_role: user.role,
        })
      }
      await loadCourses()
      setIsDialogOpen(false)
      setEditingCourse(null)
      setFormData({ name: '', code: '', instructor_email: '' })
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to save course')
    }
  }

  const handleEnroll = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!enrollCourse || !user || !enrollEmail.trim()) return
    try {
      await api.post('/courses/enroll', {
        course_id: enrollCourse.id,
        student_email: enrollEmail.trim(),
        requested_by_role: user.role,
      })
      setEnrollDialogOpen(false)
      setEnrollEmail('')
      setEnrollCourse(null)
      setAvailableStudents([])
      await loadCourses()
    } catch (err: any) {
      console.error(err)
      alert(err?.response?.data?.message || 'Failed to enroll student')
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
      onLogout={handleLogout}
      title="Courses"
    >
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">
              {user?.role.toLowerCase() === 'admin' ? 'Courses' : 'My Courses'}
            </h1>
            <p className="text-muted-foreground">
              {user?.role.toLowerCase() === 'admin' 
                ? 'Manage courses and assign instructors.' 
                : 'Manage your assigned courses and enrolled students.'}
            </p>
          </div>
          {user?.role.toLowerCase() === 'admin' && (
            <Button onClick={handleAddCourse}>
              <Plus className="mr-2 h-4 w-4" />
              Add Course
            </Button>
          )}
        </div>

        <Card>
          <CardHeader>
            <CardTitle>
              {user?.role.toLowerCase() === 'admin' ? 'All Courses' : 'Your Courses'}
            </CardTitle>
            <CardDescription>
              {user?.role.toLowerCase() === 'admin'
                ? 'Courses currently available in the system.'
                : 'Courses you are currently teaching.'}
            </CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="flex items-center justify-center py-8 text-muted-foreground">
                Loading courses...
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Course</TableHead>
                    <TableHead>Code</TableHead>
                    <TableHead>Instructor</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {courses.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                        No courses found
                      </TableCell>
                    </TableRow>
                  ) : (
                    courses.map((course) => (
                      <TableRow key={course.id}>
                        <TableCell className="font-medium">{course.id}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <BookOpen className="h-4 w-4 text-muted-foreground" />
                            {course.name}
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="secondary">{course.code}</Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <User className="h-4 w-4 text-muted-foreground" />
                            <span className="text-sm">{course.instructor_email}</span>
                            {typeof course.enrollment_count !== 'undefined' && (
                              <Badge variant="outline" className="ml-2">
                                {course.enrollment_count} enrolled
                              </Badge>
                            )}
                          </div>
                        </TableCell>
                        <TableCell className="text-right">
                          <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                              <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Open menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                              </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                              <DropdownMenuLabel>Actions</DropdownMenuLabel>
                              <DropdownMenuItem onClick={() => navigate(`/dashboard/courses/${course.id}`)}>
                                <Eye className="mr-2 h-4 w-4" />
                                View Details
                              </DropdownMenuItem>
                              {user?.role.toLowerCase() === 'admin' && (
                                <>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem onClick={() => handleEditCourse(course)}>
                                    <Pencil className="mr-2 h-4 w-4" />
                                    Edit
                                  </DropdownMenuItem>
                                  <DropdownMenuItem
                                    onClick={async () => {
                                      setEnrollCourse(course)
                                      setEnrollEmail('')
                                      await loadAvailableStudents(course)
                                      setEnrollDialogOpen(true)
                                    }}
                                  >
                                    <UserPlus className="mr-2 h-4 w-4" />
                                    Enroll student
                                  </DropdownMenuItem>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem
                                    onClick={() => handleDeleteCourse(course.id)}
                                    className="text-destructive"
                                  >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete
                                  </DropdownMenuItem>
                                </>
                              )}
                              {user?.role.toLowerCase() === 'instructor' && (
                                <>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem
                                    onClick={async () => {
                                      setEnrollCourse(course)
                                      setEnrollEmail('')
                                      await loadAvailableStudents(course)
                                      setEnrollDialogOpen(true)
                                    }}
                                  >
                                    <UserPlus className="mr-2 h-4 w-4" />
                                    Enroll student
                                  </DropdownMenuItem>
                                </>
                              )}
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>
      </div>

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="sm:max-w-[480px]">
          <DialogHeader>
            <DialogTitle>{editingCourse ? 'Edit Course' : 'Add Course'}</DialogTitle>
            <DialogDescription>
              {editingCourse
                ? 'Update the course information.'
                : 'Provide details for the new course.'}
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleSubmit} className="space-y-4">
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
                  No instructors available. Please create an instructor user first.
                </div>
              ) : (
                <Select
                  value={
                    instructors.some((inst) => inst.email === formData.instructor_email)
                      ? formData.instructor_email
                      : ''
                  }
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
              <Button type="button" variant="outline" onClick={() => setIsDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit">{editingCourse ? 'Update' : 'Create'}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Enroll student dialog */}
      <Dialog 
        open={enrollDialogOpen} 
        onOpenChange={(open) => {
          setEnrollDialogOpen(open)
          if (!open) {
            // Reset state when dialog closes
            setEnrollEmail('')
            setEnrollCourse(null)
            setAvailableStudents([])
            setAllStudentsCount(0)
            setStudentComboOpen(false)
          }
        }}
      >
        <DialogContent className="sm:max-w-[420px]">
          <DialogHeader>
            <DialogTitle>Enroll Student</DialogTitle>
            <DialogDescription>
              Select a student to enroll in {enrollCourse ? enrollCourse.name : 'this course'}.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleEnroll} className="space-y-4">
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

