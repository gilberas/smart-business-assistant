'use client';

import { ArrowLeft, ArrowRight, Check, Shield, Key, Phone, PlugZap } from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useConnectWhatsApp } from '@/hooks/useWhatsApp';
import { whatsappApi } from '@/lib/api/whatsapp';

const steps = [
    { id: 'intro', title: 'Introduction', icon: Phone },
    { id: 'credentials', title: 'Access Token', icon: Key },
    { id: 'verify', title: 'Verification', icon: Shield },
];

export default function ConnectWhatsAppPage() {
    const navigate = useNavigate();
    const connectMutation = useConnectWhatsApp();
    const [step, setStep] = useState(0);
    const [accessToken, setAccessToken] = useState('');
    const [wabaId, setWabaId] = useState('');
    const [phoneNumberId, setPhoneNumberId] = useState('');
    const [verifying, setVerifying] = useState(false);
    const [verifyOk, setVerifyOk] = useState(false);

    const CurrentIcon = steps[step]?.icon ?? Phone;

    const handleTest = async () => {
        if (!accessToken.trim()) {
            toast.error('Access token is required to test the connection');

            return;
        }

        setVerifying(true);
        setVerifyOk(false);

        try {
            const result = await whatsappApi.verify({
                access_token: accessToken.trim(),
                waba_id: wabaId.trim() || undefined,
                phone_number_id: phoneNumberId.trim() || undefined,
            });

            setVerifyOk(true);
            const name = (result.waba?.name as string) ?? null;
            const count = Array.isArray(result.phone_numbers) ? result.phone_numbers.length : 0;
            toast.success(
                name
                    ? `Connection valid (${name}${count ? `, ${count} phone number(s)` : ''})`
                    : 'Connection valid',
            );
        } catch (e) {
            const message = e instanceof Error ? e.message : 'Connection test failed';
            toast.error(message);
        } finally {
            setVerifying(false);
        }
    };

    const handleConnect = () => {
        if (!accessToken.trim()) {
            toast.error('Access token is required');

            return;
        }

        connectMutation.mutate(
            {
                access_token: accessToken.trim(),
                waba_id: wabaId.trim() || undefined,
                phone_number_id: phoneNumberId.trim() || undefined,
            },
            {
                onSuccess: () => {
                    toast.success('WhatsApp account connected successfully');
                    navigate('/whatsapp');
                },
                onError: (error: Error) => {
                    toast.error(error.message ?? 'Failed to connect WhatsApp account');
                },
            },
        );
    };

    return (
        <div className="mx-auto max-w-2xl space-y-6 p-6">
            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" onClick={() => navigate('/whatsapp')}>
                    <ArrowLeft className="h-4 w-4" />
                </Button>
                <div>
                    <h1 className="text-3xl font-bold tracking-tight">Connect WhatsApp</h1>
                    <p className="text-muted-foreground">
                        Link your Meta WhatsApp Business Account
                    </p>
                </div>
            </div>

            <div className="flex items-center justify-center gap-2">
                {steps.map((s, i) => (
                    <div key={s.id} className="flex items-center gap-2">
                        <div
                            className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium ${
                                i <= step
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground'
                            }`}
                        >
                            {i < step ? (
                                <Check className="h-4 w-4" />
                            ) : (
                                <s.icon className="h-4 w-4" />
                            )}
                        </div>
                        <span
                            className={`hidden text-sm sm:inline ${i <= step ? 'text-foreground' : 'text-muted-foreground'}`}
                        >
                            {s.title}
                        </span>
                        {i < steps.length - 1 && <div className="h-px w-8 bg-border" />}
                    </div>
                ))}
            </div>

            <Card>
                <CardHeader>
                    <div className="flex items-center gap-2">
                        <CurrentIcon className="h-5 w-5" />
                        <CardTitle>{steps[step]?.title}</CardTitle>
                    </div>
                    <CardDescription>
                        {step === 0 &&
                            'Follow the steps to connect your Meta WhatsApp Business Account (WABA).'}
                        {step === 1 &&
                            'Enter your permanent access token from the Meta Business Platform.'}
                        {step === 2 && 'Verify the connection details and complete the setup.'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {step === 0 && (
                        <div className="space-y-4">
                            <div className="rounded-lg border p-4">
                                <h3 className="mb-2 font-medium">Prerequisites</h3>
                                <ul className="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                                    <li>A Meta Business Account with WhatsApp access</li>
                                    <li>A verified WhatsApp Business Account (WABA)</li>
                                    <li>A permanent access token with WhatsApp permissions</li>
                                    <li>A phone number registered and verified in WhatsApp</li>
                                </ul>
                            </div>
                            <div className="rounded-lg border p-4">
                                <h3 className="mb-2 font-medium">How to get your access token</h3>
                                <ol className="list-inside list-decimal space-y-1 text-sm text-muted-foreground">
                                    <li>Go to the Meta Business Platform</li>
                                    <li>
                                        Select your app and navigate to WhatsApp &gt; Getting
                                        Started
                                    </li>
                                    <li>Copy the permanent access token</li>
                                    <li>Note your WABA ID and Phone Number ID (optional)</li>
                                </ol>
                            </div>
                        </div>
                    )}

                    {step === 1 && (
                        <div className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="access-token">Permanent Access Token *</Label>
                                <Input
                                    id="access-token"
                                    type="password"
                                    placeholder="EAAx..."
                                    value={accessToken}
                                    onChange={(e) => setAccessToken(e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Your Meta permanent access token with WhatsApp permissions
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="waba-id">WABA ID (optional)</Label>
                                <Input
                                    id="waba-id"
                                    placeholder="123456789"
                                    value={wabaId}
                                    onChange={(e) => setWabaId(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone-id">Phone Number ID (optional)</Label>
                                <Input
                                    id="phone-id"
                                    placeholder="123456789"
                                    value={phoneNumberId}
                                    onChange={(e) => setPhoneNumberId(e.target.value)}
                                />
                            </div>
                        </div>
                    )}

                    {step === 2 && (
                        <div className="space-y-4">
                            <div className="rounded-lg border p-4">
                                <h3 className="mb-2 font-medium">Connection Summary</h3>
                                <dl className="space-y-2 text-sm">
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Access Token</dt>
                                        <dd className="font-mono">
                                            {accessToken
                                                ? `${accessToken.substring(0, 10)}...`
                                                : 'Not provided'}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">WABA ID</dt>
                                        <dd>{wabaId || 'Auto-detected'}</dd>
                                    </div>
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Phone Number ID</dt>
                                        <dd>{phoneNumberId || 'Auto-detected'}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                                <p className="text-sm text-yellow-800 dark:text-yellow-200">
                                    Your access token will be encrypted before storage. The system
                                    will auto-sync your phone numbers and configure the webhook
                                    endpoint.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleTest}
                                disabled={verifying || !accessToken}
                                className="w-full"
                            >
                                {verifying ? (
                                    <Spinner className="mr-2 h-4 w-4" />
                                ) : (
                                    <PlugZap className="mr-2 h-4 w-4" />
                                )}
                                {verifyOk ? 'Test again' : 'Test Connection'}
                            </Button>
                            {verifyOk && (
                                <p className="text-center text-sm text-green-600 dark:text-green-400">
                                    Connection verified with Meta. You can now connect the account.
                                </p>
                            )}
                        </div>
                    )}

                    <div className="flex justify-between pt-4">
                        <Button
                            variant="outline"
                            onClick={() => setStep(Math.max(0, step - 1))}
                            disabled={step === 0}
                        >
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Previous
                        </Button>

                        {step < steps.length - 1 ? (
                            <Button
                                onClick={() => setStep(step + 1)}
                                disabled={step === 1 && !accessToken}
                            >
                                Next
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Button>
                        ) : (
                            <Button
                                onClick={handleConnect}
                                disabled={connectMutation.isPending || !accessToken}
                            >
                                {connectMutation.isPending ? (
                                    <Spinner className="mr-2" />
                                ) : (
                                    <Check className="mr-2 h-4 w-4" />
                                )}
                                Connect Account
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
