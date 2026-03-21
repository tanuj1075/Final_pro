import LoginButton from '@/components/LoginButton';

export default function HomePage() {
  return (
    <main className="mx-auto min-h-screen max-w-3xl px-6 py-20">
      <h1 className="text-4xl font-bold">Clean Frontend (UI Only)</h1>
      <p className="mt-4 text-zinc-300">
        This layer is only for UI. OAuth and database access are handled by backend and database layers.
      </p>
      <div className="mt-8">
        <LoginButton />
      </div>
    </main>
  );
}
