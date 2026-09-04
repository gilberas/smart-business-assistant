import { Link } from 'react-router-dom';
import { ShieldAlert, Home, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

export default function AccessDeniedPage() {
    return (
        <div className="flex min-h-[60vh] flex-col items-center justify-center gap-6 px-4">
            <div className="rounded-full bg-destructive/10 p-6">
                <ShieldAlert className="h-12 w-12 text-destructive" />
            </div>

            <div className="space-y-2 text-center">
                <h1 className="text-4xl font-bold tracking-tight">403</h1>
                <h2 className="text-2xl font-semibold">Access Denied</h2>
                <p className="max-w-md text-muted-foreground">
                    You do not have permission to access this page. If you believe this is an error,
                    please contact your administrator.
                </p>
            </div>

            <div className="flex gap-3">
                <Button
                    variant="outline"
                    onClick={() => window.history.back()}
                >
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Go Back
                </Button>
                <Button asChild>
                    <Link to="/">
                        <Home className="mr-2 h-4 w-4" />
                        Go to Dashboard
                    </Link>
                </Button>
            </div>
        </div>
    );
}
