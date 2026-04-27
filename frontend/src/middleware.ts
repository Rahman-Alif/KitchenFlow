import { NextRequest, NextResponse } from 'next/server';

const PUBLIC_ROUTES = [
  '/login',
  '/reset-password/request',
  '/reset-password/confirm',
];

const ROLE_ROUTES: Record<string, string[]> = {
  admin:         ['/dashboard', '/users', '/categories', '/menu', '/orders-history', '/messages', '/profile'],
  kitchen_staff: ['/orders', '/menu', '/messages', '/profile'],
  user:          ['/menu', '/orders', '/profile'],
};

const ROLE_HOME: Record<string, string> = {
  admin: '/dashboard',
  kitchen_staff: '/orders',
  user: '/menu',
};

function isPublicRoute(pathname: string): boolean {
  return PUBLIC_ROUTES.some((route) => pathname.startsWith(route));
}

function isAllowedForRole(pathname: string, role: string): boolean {
  const allowed = ROLE_ROUTES[role] ?? [];
  return allowed.some((route) => pathname.startsWith(route));
}

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  const token = request.cookies.get('kf_token')?.value ?? null;
  const userRaw = request.cookies.get('kf_user')?.value ?? null;
  const isLoggedIn = !!token && !!userRaw;

  if (!isLoggedIn) {
    if (isPublicRoute(pathname)) return NextResponse.next();
    return NextResponse.redirect(new URL('/login', request.url));
  }

  let role: string | null = null;
  try {
    const user = JSON.parse(decodeURIComponent(userRaw!));
    role = user?.role ?? null;
  } catch {
    const response = NextResponse.redirect(new URL('/login', request.url));
    response.cookies.delete('kf_token');
    response.cookies.delete('kf_user');
    return response;
  }

  if (isPublicRoute(pathname)) {
    return NextResponse.redirect(new URL(ROLE_HOME[role!] ?? '/login', request.url));
  }

  if (pathname === '/') {
    return NextResponse.redirect(new URL(ROLE_HOME[role!] ?? '/login', request.url));
  }

  if (role && !isAllowedForRole(pathname, role)) {
    return NextResponse.redirect(new URL(ROLE_HOME[role] ?? '/login', request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    '/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp)$).*)',
  ],
};