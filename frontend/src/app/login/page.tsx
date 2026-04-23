// frontend/src/app/login/page.tsx
// Server Component — just imports the CSS and renders the client form.
// No logic lives here.

import './login.css';
import LoginForm from '@/components/auth/LoginForm';

export default function LoginPage() {
  return <LoginForm />;
}