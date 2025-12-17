import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Button } from '../components/ui/button'
import { DashboardLayout } from '../components/DashboardLayout'
import { Users, BookOpen, CalendarClock, ArrowRight } from 'lucide-react'

interface User {
  id: number
  name: string
  email: string
  role: string
}

export default function Dashboard() {
  const navigate = useNavigate()
  const { role } = useParams()
  const [user, setUser] = useState<User | null>(null)

  useEffect(() => {
    // Check if user is logged in
    const userStr = localStorage.getItem('user')
    if (!userStr) {
      navigate('/login')
      return
    }

    const userData = JSON.parse(userStr)
    setUser(userData)

    // Redirect to role-specific dashboard if role param doesn't match
    if (role && role !== userData.role.toLowerCase()) {
      navigate(`/dashboard/${userData.role.toLowerCase()}`)
    } else if (!role) {
      navigate(`/dashboard/${userData.role.toLowerCase()}`)
    }
  }, [navigate, role])

  const handleLogout = () => {
    localStorage.removeItem('user')
    localStorage.removeItem('token')
    navigate('/login')
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
    >
      <div className="space-y-6">
        <div>
          <p className="text-muted-foreground">
            Welcome to your {user.role} dashboard
          </p>
        </div>

        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>User Information</CardTitle>
              <CardDescription>Your account details</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">User ID:</span>
                  <span className="text-sm font-medium">{user.id}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">Email:</span>
                  <span className="text-sm font-medium">{user.email}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">Role:</span>
                  <span className="text-sm font-medium">{user.role}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
              <CardDescription>Common tasks and shortcuts</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {user.role.toLowerCase() === 'admin' && (
                  <Button
                    variant="outline"
                    className="w-full justify-between"
                    onClick={() => navigate('/dashboard/users')}
                  >
                    <div className="flex items-center gap-2">
                      <Users className="h-4 w-4" />
                      <span>Users</span>
                    </div>
                    <ArrowRight className="h-4 w-4" />
                  </Button>
                )}
                {['admin', 'instructor', 'student'].includes(user.role.toLowerCase()) && (
                  <>
                    <Button
                      variant="outline"
                      className="w-full justify-between"
                      onClick={() => navigate('/dashboard/courses')}
                    >
                      <div className="flex items-center gap-2">
                        <BookOpen className="h-4 w-4" />
                        <span>Courses</span>
                      </div>
                      <ArrowRight className="h-4 w-4" />
                    </Button>
                    <Button
                      variant="outline"
                      className="w-full justify-between"
                      onClick={() => navigate('/dashboard/sessions')}
                    >
                      <div className="flex items-center gap-2">
                        <CalendarClock className="h-4 w-4" />
                        <span>Sessions</span>
                      </div>
                      <ArrowRight className="h-4 w-4" />
                    </Button>
                  </>
                )}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent Activity</CardTitle>
              <CardDescription>Your latest actions</CardDescription>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-muted-foreground">
                Recent activity will be displayed here.
              </p>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Overview</CardTitle>
            <CardDescription>
              Dashboard content for {user.role} will be implemented here.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <p className="text-muted-foreground">
              This is your main dashboard area. You can add more content and widgets here based on your role and requirements.
            </p>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  )
}

