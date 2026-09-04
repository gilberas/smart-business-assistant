import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import apiClient from '@/lib/api/client';

export default function VerifyEmailPage() {
    const [searchParams] = useSearchParams();
    const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
    const [message, setMessage] = useState('');

    useEffect(() => {
        const id = searchParams.get('id');
        const hash = searchParams.get('hash');
        const signature = searchParams.get('signature');
        const expires = searchParams.get('expires');

        if (id && hash) {
            apiClient
                .get(`/auth/email/verify/${id}/${hash}`, {
                    params: { signature, expires },
                })
                .then(() => {
                    setStatus('success');
                    setMessage('Email verified successfully!');
                })
                .catch((err) => {
                    setStatus('error');
                    setMessage(err.response?.data?.message || 'Verification failed');
                });
        } else {
            setStatus('error');
            setMessage('Invalid verification link');
        }
    }, [searchParams]);

    const handleResend = async () => {
        try {
            await apiClient.post('/auth/email/resend');
            setMessage('Verification email sent!');
        } catch (err) {
            setMessage('Failed to resend verification email');
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center px-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <CardTitle className="text-2xl">Email Verification</CardTitle>
                    <CardDescription>
                        {status === 'loading' && 'Verifying your email...'}
                        {status === 'success' && 'Your email has been verified!'}
                        {status === 'error' && 'Verification failed'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col items-center gap-4">
                    {status === 'loading' && <Spinner className="h-8 w-8" />}
                    {message && (
                        <p className="text-center text-sm text-muted-foreground">{message}</p>
                    )}
                    {status === 'success' && (
                        <Button asChild>
                            <a href="/login">Go to login</a>
                        </Button>
                    )}
                    {status === 'error' && (
                        <Button variant="outline" onClick={handleResend}>
                            Resend verification email
                        </Button>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
