import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { Button } from '../components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components/ui/card'
import { Input } from '../components/ui/input'
import { Label } from '../components/ui/label'

// Use proxy for consistency - all requests go through Gateway
const api = axios.create({ baseURL: '/api' })

interface LoginResponse {
  success: boolean
  message?: string
  user?: {
    id: number
    name: string
    email: string
    role: string
  }
  token?: string
}

export default function Login() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  })
  const [error, setError] = useState<string>('')
  const [loading, setLoading] = useState(false)

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    })
    setError('')
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    // Basic validation
    if (!formData.email || !formData.password) {
      setError('Please fill in all fields')
      setLoading(false)
      return
    }

    try {
      const response = await api.post<LoginResponse>(
        '/auth/login',
        formData,
        {
          headers: {
            'Content-Type': 'application/json',
          },
        }
      )

      if (response.data.success && response.data.user) {
        // Store user data and token in localStorage
        localStorage.setItem('user', JSON.stringify(response.data.user))
        if (response.data.token) {
          localStorage.setItem('token', response.data.token)
        }

        // Redirect based on role
        const role = response.data.user.role.toLowerCase()
        if (role === 'admin') {
          navigate('/dashboard/admin')
        } else if (role === 'instructor') {
          navigate('/dashboard/instructor')
        } else if (role === 'student') {
          navigate('/dashboard/student')
        } else {
          navigate('/dashboard')
        }
      } else {
        setError(response.data.message || 'Login failed. Please check your credentials.')
      }
    } catch (err: any) {
      if (err.response?.data?.message) {
        setError(err.response.data.message)
      } else {
        setError('An error occurred. Please try again.')
      }
      console.error('Login error:', err)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen w-full items-center justify-center p-6 bg-muted/50">
      <div className="w-full max-w-sm">
        <Card>
          <CardHeader className="space-y-1">
            <div className="flex items-center justify-center mb-4">
              <div className="flex h-12 w-12 items-center justify-center rounded-md bg-primary text-primary-foreground font-bold text-xl">
                S
              </div>
            </div>
            <CardTitle className="text-2xl text-center">Login to SAMS</CardTitle>
            <CardDescription className="text-center">
              Enter your credentials to access your account
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <div className="p-3 text-sm text-destructive bg-destructive/10 border border-destructive/20 rounded-md">
                  {error}
                </div>
              )}
              
              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  name="email"
                  type="email"
                  placeholder="name@example.com"
                  value={formData.email}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </div>

              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label htmlFor="password">Password</Label>
                  <a
                    href="#"
                    className="text-sm text-primary hover:underline"
                    onClick={(e) => {
                      e.preventDefault()
                      // TODO: Implement forgot password
                    }}
                  >
                    Forgot password?
                  </a>
                </div>
                <Input
                  id="password"
                  name="password"
                  type="password"
                  placeholder="Enter your password"
                  value={formData.password}
                  onChange={handleChange}
                  required
                  disabled={loading}
                />
              </div>

              <Button
                type="submit"
                className="w-full"
                disabled={loading}
              >
                {loading ? 'Logging in...' : 'Login'}
              </Button>

              <div className="text-center text-sm text-muted-foreground">
                Don't have an account?{' '}
                <a
                  href="#"
                  className="text-primary hover:underline"
                  onClick={(e) => {
                    e.preventDefault()
                    // TODO: Navigate to signup
                  }}
                >
                  Sign up
                </a>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}

