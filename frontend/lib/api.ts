const BASE_URL = process.env.NEXT_PUBLIC_API_BASE_URL;

function ensureBaseUrl(): string {
  if (!BASE_URL) {
    throw new Error('NEXT_PUBLIC_API_BASE_URL is not configured');
  }

  try {
    return new URL(BASE_URL).toString().replace(/\/$/, '');
  } catch (error) {
    throw new Error('NEXT_PUBLIC_API_BASE_URL is not a valid URL');
  }
}

export function buildApiUrl(path: string): string {
  if (!path || typeof path !== 'string') {
    throw new Error('API path is required');
  }

  const trimmed = path.startsWith('/') ? path : `/${path}`;
  return `${ensureBaseUrl()}${trimmed}`;
}
