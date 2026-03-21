import { buildApiUrl } from '@/lib/api';

export default function LoginButtons() {
  return (
    <div className="mt-8 flex flex-wrap gap-3">
      <a
        href={buildApiUrl('/auth/google')}
        className="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white hover:bg-blue-500"
      >
        Continue with Google
      </a>
    </div>
  );
}
