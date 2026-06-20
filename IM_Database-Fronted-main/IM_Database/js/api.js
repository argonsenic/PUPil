// API Configuration
const API_BASE_URL = './api';

// Check if user is authenticated
async function checkAuth() {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/login.php`, {
      method: 'GET',
      credentials: 'include'
    });
    
    const user = JSON.parse(localStorage.getItem('user'));
    return user;
  } catch (error) {
    return null;
  }
}

// Store user data after login
function storeUser(userData) {
  localStorage.setItem('user', JSON.stringify(userData));
}

// Get stored user data
function getUser() {
  return JSON.parse(localStorage.getItem('user'));
}

// Clear user data (logout)
function clearUser() {
  localStorage.removeItem('user');
}

// API call helper
async function apiCall(endpoint, options = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const defaultOptions = {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      ...options.headers
    }
  };
  
  const finalOptions = { ...defaultOptions, ...options };
  
  try {
    const response = await fetch(url, finalOptions);
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.message || 'API request failed');
    }
    
    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// Login function
async function login(username, password) {
  const data = await apiCall('/auth/login.php', {
    method: 'POST',
    body: JSON.stringify({ username, password })
  });
  
  if (data.success) {
    storeUser(data.data);
  }
  
  return data;
}

// Register function
async function register(userData) {
  return await apiCall('/auth/register.php', {
    method: 'POST',
    body: JSON.stringify(userData)
  });
}

// Logout function
async function logout() {
  try {
    await apiCall('/auth/logout.php', {
      method: 'POST'
    });
  } catch (error) {
    console.error('Logout error:', error);
  } finally {
    clearUser();
    window.location.href = 'login.html';
  }
}

// Get student profile
async function getStudentProfile() {
  const user = getUser();
  if (!user || !user.user_id) {
    throw new Error('User not authenticated');
  }
  
  return await apiCall('/students/index.php');
}

// Update student profile
async function updateStudentProfile(studentId, userData) {
  return await apiCall(`/students/update.php?id=${studentId}`, {
    method: 'PUT',
    body: JSON.stringify(userData)
  });
}

// Get attendance records
async function getAttendance(studentId, subjectId = null, date = null) {
  let url = `/attendance/index.php?student_id=${studentId}`;
  if (subjectId) url += `&subject_id=${subjectId}`;
  if (date) url += `&date=${date}`;
  
  return await apiCall(url);
}

// Get subjects for attendance
async function getSubjects() {
  return await apiCall('/attendance/subjects.php');
}

// Get students by subject
async function getStudentsBySubject(subjectId) {
  return await apiCall(`/attendance/students.php?subject_id=${subjectId}`);
}

// Log attendance
async function logAttendance(attendanceData) {
  return await apiCall('/attendance/index.php', {
    method: 'POST',
    body: JSON.stringify(attendanceData)
  });
}

// Get enrollment records
async function getEnrollments(studentId = null, subjectId = null) {
  let url = '/enrollment/index.php';
  const params = [];
  
  if (studentId) params.push(`student_id=${studentId}`);
  if (subjectId) params.push(`subject_id=${subjectId}`);
  
  if (params.length > 0) {
    url += '?' + params.join('&');
  }
  
  return await apiCall(url);
}

// Create enrollment (admin only)
async function createEnrollment(enrollmentData) {
  return await apiCall('/enrollment/index.php', {
    method: 'POST',
    body: JSON.stringify(enrollmentData)
  });
}

// Redirect if not authenticated
function requireAuth() {
  const user = getUser();
  if (!user) {
    window.location.href = 'login.html';
    return false;
  }
  return true;
}

// Update navigation with user info
function updateNavigation() {
  const user = getUser();
  if (user) {
    const userElements = document.querySelectorAll('.user-name');
    userElements.forEach(el => {
      el.textContent = user.full_name || user.username;
    });
  }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  updateNavigation();
  
  // Add logout handler to logout links
  const logoutLinks = document.querySelectorAll('a[href="login.html"]');
  logoutLinks.forEach(link => {
    if (link.textContent.includes('Logout') || link.textContent.includes('Log out')) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        logout();
      });
    }
  });
});
