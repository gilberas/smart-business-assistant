import { Link } from 'react-router-dom';
import { ShieldAlert } from 'lucide-react';
import { Button } from '@/components/ui/button';

export function PermissionDenied() {
    return (
        <div className="flex min-h-[400px] flex-col items-center justify-center gap-4">
            <div className="rounded-full bg-destructive/10 p-4">
                <ShieldAlert className="h-8 w-8 text-destructive" />
            </div>
            <h2 className="text-xl font-semibold">Permission Denied</h2>
            <p className="max-w-md text-center text-muted-foreground">
                You do not have the required permissions to perform this action.
            </p>
            <div className="flex gap-2">
                <Button variant="outline" onClick={() => window.history.back()}>
                    Go Back
                </Button>
                <Button asChild>
                    <Link to="/">Go to Dashboard</Link>
                </Button>
            </div>
        </div>
    );
}
