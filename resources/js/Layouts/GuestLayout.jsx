import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg">
                            <span className="text-2xl font-bold">S</span>
                        </div>
                        <span className="text-3xl font-bold text-gray-800 tracking-tight">
                            Smart<span className="text-blue-600">Schools</span>
                        </span>
                    </div>
                </Link>
            </div>

            <div className="mt-8 w-full overflow-hidden bg-white px-8 py-8 shadow-xl sm:max-w-md sm:rounded-2xl border border-gray-100">
                <div className="mb-6 text-center">
                    <h2 className="text-2xl font-bold text-gray-800">Bienvenue blabla</h2>
                    <p className="text-sm text-gray-500 mt-1">Connectez-vous pour accéder à votre espace</p>
                </div>
                {children}
            </div>
        </div>
    );
}
