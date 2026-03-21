const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL || 'http://localhost:5000';

export async function loginWithGoogle() {
  window.location.href = `${BASE_URL}/auth/google`;
}
