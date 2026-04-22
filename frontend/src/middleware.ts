import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

const PUBLIC_ROUTES = ['/login'];

const ROLE_ROUTES: Record<string, string[]> = {
  kitchen_staff: ['/kitchen'],
  user: ['/user'],
  admin: ['/admin'],
};

export function middleware(request: NextRequest) {
  const token = request.cookies.get('token')?.value;
  const role = request.cookies.get('role')?.value;
  const pathname = request.nextUrl.pathname;

  if (PUBLIC_ROUTES.includes(pathname)) {
    if (token) {
      return NextResponse.redirect(new URL(getRoleHome(role), request.url));
    }
    return NextResponse.next();
  }

  if (!token) {
    return NextResponse.redirect(new URL('/login', request.url));
  }

  for (const [requiredRole, routes] of Object.entries(ROLE_ROUTES)) {
    const isProtectedRoute = routes.some((r) => pathname.startsWith(r));
    if (isProtectedRoute && role !== requiredRole) {
      return NextResponse.redirect(new URL(getRoleHome(role), request.url));
    }
  }

  return NextResponse.next();
}

function getRoleHome(role?: string): string {
  switch (role) {
    case 'kitchen_staff': return '/kitchen/orders';
    case 'user': return '/user/menu';
    case 'admin': return '/admin/dashboard';
    default: return '/login';
  }
}

export const config = {
  matcher: ['/((?!api|_next/static|_next/image|favicon.ico).*)'],
};