import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useForgotPassword } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Building2, ArrowLeft } from 'lucide-react';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const forgotPassword = useForgotPassword();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        forgotPassword.mutate({ email });
    };

    return (
        <div className="space-y-8">
            <div className="text-center lg:text-left">
                <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-[var(--primary)]/10 lg:mx-0">
                    <Building2 className="h-6 w-6 text-[var(--primary)]" />
                </div>
                <h1 className="text-2xl font-bold tracking-tight">Forgot password?</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Enter your email and we&apos;ll send you a reset link
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
                {forgotPassword.isSuccess && (
                    <div className="rounded-lg border border-emerald-500/20 bg-emerald-500/5 p-3 text-sm text-emerald-600 dark:text-emerald-400">
                        {forgotPassword.data}
                    </div>
                )}
                {forgotPassword.error && (
                    <div className="rounded-lg border border-destructive/20 bg-destructive/5 p-3 text-sm text-destructive">
                        {forgotPassword.error instanceof Error
                            ? forgotPassword.error.message
                            : 'Failed to send reset link'}
                    </div>
                )}

                <div className="space-y-2">
                    <Label htmlFor="email" className="text-sm font-medium">
                        Email
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="name@company.com"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                        autoComplete="email"
                        className="h-10"
                    />
                </div>

                <Button
                    type="submit"
                    className="w-full h-10"
                    disabled={forgotPassword.isPending}
                >
                    {forgotPassword.isPending ? <Spinner className="mr-2" /> : null}
                    Send reset link
                </Button>
            </form>

            <Link
                to="/login"
                className="flex items-center justify-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
                <ArrowLeft className="h-4 w-4" />
                Back to sign in
            </Link>
        </div>
    );
}
