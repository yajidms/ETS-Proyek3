'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

const TOKEN_KEY = 'jwt_token';

export default function HomePage() {
  const router = useRouter();

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const token = localStorage.getItem(TOKEN_KEY);
    const role = localStorage.getItem(`${TOKEN_KEY}_role`);
    const exp = Number(localStorage.getItem(`${TOKEN_KEY}_exp`));

    if (!token || (exp && exp < Date.now())) {
      router.replace('/login');
      return;
    }

    if (role === 'Admin') {
      router.replace('/admin');
    } else {
      router.replace('/public');
    }
  }, [router]);

  return (
    <div className="page page--centered">
      <p>Mengalihkan...</p>
    </div>
  );
}
