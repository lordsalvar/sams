import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { Plus, MoreHorizontal, Pencil, Trash2, BookOpen, User, UserPlus } from 'lucide-react'
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

interface Course {
  id: number
  name: string
  code: string
  instructor_email: string
  enrollment_count?: number
}

const api = axios.create({ baseURL: '/api' })

export default function Courses() {
  const navigate = useNavigate()
  const [user, setUser] = useState<{ role: string; username: string } | null>(null)
  const [courses, setCourses] = useState<Course[]>([])
  const [loading, setLoading] = useState(true)
  const [isDialogOpen, setIsDialogOpen] = useState(false)
  const [editingCourse, setEditingCourse] = useState<Course | null>(null)
  const [formData, setFormData] = useState({
    name: '',
    code: '',
    instructor_email: '',
  })
  const [enrollDialogOpen, setEnrollDialogOpen] = useState(false)
  const [enrollCourse, setEnrollCourse] = useState<Course | null>(null)
  const [enrollEmail, setEnrollEmail] = useState('')

  useEffect(() => {
    const userStr = localStorage.getItem('user')
    if (!userStr) {
      navigate('/login')
      return
    }

    const userData = JSON.parse(userStr)
    setUser(userData)

    // Only admins can manage courses
    if (userData.role.toLowerCase() !== 'admin') {
      navigate('/dashboard')
      return
    }

    loadCourses()
  }, [navigate])

  const loadCourses = async () => {
    setLoading(true)
    try {
      const res = await api.get('/courses')
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

  const handleLogout = () => {
    localStorage.removeItem('user')
    localStorage.removeItem('token')
    navigate('/login')
  }

  const handleAddCourse = () => {
    setEditingCourse(null)
    setFormData({
      name: '',
      code: '',
      instructor_email: '',
    })
    setIsDialogOpen(true)
  }

  const handleEditCourse = (course: Course) => {
    setEditingCourse(course)
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
    if (!enrollCourse || !user) return
    try {
      await api.post('/courses/enroll', {
        course_id: enrollCourse.id,
        student_email: enrollEmail.trim(),
        requested_by_role: user.role,
      })
      setEnrollDialogOpen(false)
      setEnrollEmail('')
      setEnrollCourse(null)
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
      username={user.username}
      onLogout={handleLogout}
      title="Courses"
    >
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Courses</h1>
            <p className="text-muted-foreground">Manage courses and assign instructors.</p>
          </div>
          <Button onClick={handleAddCourse}>
            <Plus className="mr-2 h-4 w-4" />
            Add Course
          </Button>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>All Courses</CardTitle>
            <CardDescription>Courses currently available in the system.</CardDescription>
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
                              <DropdownMenuItem onClick={() => handleEditCourse(course)}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                              </DropdownMenuItem>
                              <DropdownMenuItem
                                onClick={() => {
                                  setEnrollCourse(course)
                                  setEnrollDialogOpen(true)
                                  setEnrollEmail('')
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
              <Label htmlFor="instructor_email">Instructor Email</Label>
              <Input
                id="instructor_email"
                type="email"
                value={formData.instructor_email}
                onChange={(e) => setFormData({ ...formData, instructor_email: e.target.value })}
                required
              />
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
      <Dialog open={enrollDialogOpen} onOpenChange={setEnrollDialogOpen}>
        <DialogContent className="sm:max-w-[420px]">
          <DialogHeader>
            <DialogTitle>Enroll student</DialogTitle>
            <DialogDescription>
              Add a student to {enrollCourse ? enrollCourse.name : 'this course'}.
            </DialogDescription>
          </DialogHeader>
          <form onSubmit={handleEnroll} className="space-y-4">
            <div className="grid gap-2">
              <Label htmlFor="student_email">Student Email</Label>
              <Input
                id="student_email"
                type="email"
                value={enrollEmail}
                onChange={(e) => setEnrollEmail(e.target.value)}
                required
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEnrollDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit">Enroll</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </DashboardLayout>
  )
}

