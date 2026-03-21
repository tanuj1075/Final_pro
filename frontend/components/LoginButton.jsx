'use client';

import { loginWithGoogle } from '../services/api';

export default function LoginButton() {
  return (
    <button
      onClick={loginWithGoogle}
      className="rounded-md bg-red-600 px-4 py-2 text-white hover:bg-red-500"
    >
      Login with Google
    </button>
  );
}
