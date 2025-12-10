import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import LandingPage from './pages/LandingPage'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import Users from './pages/Users'
import Courses from './pages/Courses'
import CourseDetail from './pages/CourseDetail'
import AttendanceScan from './pages/AttendanceScan'
import AttendanceDisplay from './pages/AttendanceDisplay'
import AttendanceSessions from './pages/AttendanceSessions'
import AttendanceAnalytics from './pages/AttendanceAnalytics'
import './App.css'

function App(): JSX.Element {
  return (
    <BrowserRouter
      future={{
        v7_startTransition: true,
        v7_relativeSplatPath: true,
      }}
    >
      <Routes>
        <Route path="/" element={<LandingPage />} />
        <Route path="/login" element={<Login />} />
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/dashboard/:role" element={<Dashboard />} />
        <Route path="/dashboard/users" element={<Users />} />
        <Route path="/dashboard/courses" element={<Courses />} />
        <Route path="/dashboard/courses/:courseId" element={<CourseDetail />} />
        <Route path="/dashboard/courses/:courseId/attendance-sessions" element={<AttendanceSessions />} />
        <Route path="/dashboard/courses/:courseId/attendance-display" element={<AttendanceDisplay />} />
        <Route path="/dashboard/courses/:courseId/attendance-analytics" element={<AttendanceAnalytics />} />
        <Route path="/attendance-scan" element={<AttendanceScan />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
