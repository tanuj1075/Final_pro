import LoginButtons from '@/components/LoginButtons';

export default function HomePage() {
  return (
    <main className="mx-auto min-h-screen max-w-3xl px-6 py-20">
      <h1 className="text-4xl font-bold">Frontend Layer (Next.js App Router)</h1>
      <p className="mt-4 text-zinc-300">
        This frontend contains UI only. It talks to the backend via NEXT_PUBLIC_API_BASE_URL and has no direct
        database or secret logic.
      </p>
      <LoginButtons />
    </main>
  );
}
