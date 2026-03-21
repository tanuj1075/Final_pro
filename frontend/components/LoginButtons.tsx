import { buildApiUrl } from '@/lib/api';

export default function LoginButtons() {
  let googleAuthUrl = '';
  let configError = '';

  try {
    googleAuthUrl = buildApiUrl('/auth/google');
  } catch (error) {
    configError = 'Login is unavailable right now. Please contact support.';
    console.error('Unable to build Google auth URL:', error);
  }

  return (
    <div className="mt-8 flex flex-wrap gap-3">
      {googleAuthUrl ? (
        <a
          href={googleAuthUrl}
          className="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-500"
        >
          Continue with Google
        </a>
      ) : (
        <p className="rounded-lg bg-red-900/40 px-4 py-2 text-sm text-red-200">{configError}</p>
      )}
    </div>
  );
}
