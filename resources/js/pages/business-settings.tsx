import { useState } from 'react';
import { useBusinessStore } from '@/stores/business-store';
import {
    useCurrentBusiness,
    useUpdateBusiness,
    useBusinessSettings,
    useUpdateBusinessSettings,
} from '@/hooks/useBusiness';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function BusinessSettingsPage() {
    const { currentBusiness } = useBusinessStore();
    const { isLoading: loadingBusiness } = useCurrentBusiness();
    const { data: settings, isLoading: loadingSettings } = useBusinessSettings(currentBusiness?.id ?? null);

    const [name, setName] = useState(currentBusiness?.name || '');
    const [email, setEmail] = useState(currentBusiness?.email || '');
    const [phone, setPhone] = useState(currentBusiness?.phone || '');
    const [website, setWebsite] = useState(currentBusiness?.website || '');
    const [timezone, setTimezone] = useState(currentBusiness?.timezone || 'UTC');
    const [locale, setLocale] = useState(currentBusiness?.locale || 'en');
    const [currency, setCurrency] = useState(currentBusiness?.currency || 'USD');
    const [industry, setIndustry] = useState(currentBusiness?.industry || '');

    const updateBusiness = useUpdateBusiness();
    const updateSettings = useUpdateBusinessSettings(currentBusiness?.id ?? null);

    if (loadingBusiness || !currentBusiness) {
        return (
            <div className="container mx-auto max-w-2xl space-y-6 p-6">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-64 w-full" />
            </div>
        );
    }

    const handleBusinessSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        updateBusiness.mutate({
            id: currentBusiness.id,
            payload: { name, email, phone, website, timezone, locale, currency, industry },
        });
    };

    return (
        <div className="container mx-auto max-w-3xl space-y-6 p-6">
            <div>
                <h1 className="text-3xl font-bold">Business Settings</h1>
                <p className="text-muted-foreground">Manage your business configuration</p>
            </div>

            <Tabs defaultValue="general">
                <TabsList>
                    <TabsTrigger value="general">General</TabsTrigger>
                    <TabsTrigger value="business-hours">Business Hours</TabsTrigger>
                    <TabsTrigger value="ai">AI Configuration</TabsTrigger>
                </TabsList>

                <TabsContent value="general" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Business Information</CardTitle>
                            <CardDescription>Update your business details</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleBusinessSubmit} className="space-y-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Business Name</Label>
                                        <Input
                                            id="name"
                                            value={name}
                                            onChange={(e) => setName(e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="industry">Industry</Label>
                                        <Select value={industry} onValueChange={setIndustry}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select industry" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="technology">Technology</SelectItem>
                                                <SelectItem value="retail">Retail</SelectItem>
                                                <SelectItem value="healthcare">Healthcare</SelectItem>
                                                <SelectItem value="finance">Finance</SelectItem>
                                                <SelectItem value="other">Other</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={email}
                                            onChange={(e) => setEmail(e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            value={phone}
                                            onChange={(e) => setPhone(e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="website">Website</Label>
                                        <Input
                                            id="website"
                                            value={website}
                                            onChange={(e) => setWebsite(e.target.value)}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="currency">Currency</Label>
                                        <Select value={currency} onValueChange={setCurrency}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="USD">USD</SelectItem>
                                                <SelectItem value="EUR">EUR</SelectItem>
                                                <SelectItem value="GBP">GBP</SelectItem>
                                                <SelectItem value="BRL">BRL</SelectItem>
                                                <SelectItem value="JPY">JPY</SelectItem>
                                                <SelectItem value="TZS">TZS</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="timezone">Timezone</Label>
                                        <Select value={timezone} onValueChange={setTimezone}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="UTC">UTC</SelectItem>
                                                <SelectItem value="America/New_York">Eastern</SelectItem>
                                                <SelectItem value="America/Chicago">Central</SelectItem>
                                                <SelectItem value="America/Denver">Mountain</SelectItem>
                                                <SelectItem value="America/Los_Angeles">Pacific</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="locale">Language</Label>
                                        <Select value={locale} onValueChange={setLocale}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="en">English</SelectItem>
                                                <SelectItem value="es">Spanish</SelectItem>
                                                <SelectItem value="fr">French</SelectItem>
                                                <SelectItem value="de">German</SelectItem>
                                                <SelectItem value="pt">Portuguese</SelectItem>
                                                <SelectItem value="sw">Swahili (Kiswahili)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {updateBusiness.isSuccess && (
                                    <p className="text-sm text-green-600">Business updated successfully</p>
                                )}

                                <Button type="submit" disabled={updateBusiness.isPending}>
                                    {updateBusiness.isPending ? <Spinner className="mr-2" /> : null}
                                    Save changes
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="business-hours" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Business Hours</CardTitle>
                            <CardDescription>Configure your operating hours</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground">
                                Business hours configuration will be available in the next update.
                            </p>
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="ai" className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>AI Configuration</CardTitle>
                            <CardDescription>Configure your AI provider settings</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {loadingSettings ? (
                                <Skeleton className="h-32 w-full" />
                            ) : (
                                <div className="space-y-4">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>AI Provider</Label>
                                            <Input value={settings?.ai_provider || 'Not configured'} disabled />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>AI Model</Label>
                                            <Input value={settings?.ai_model || 'Not configured'} disabled />
                                        </div>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        AI provider settings can be configured after connecting your API keys.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            <Separator />

            <Card className="border-destructive/50">
                <CardHeader>
                    <CardTitle className="text-destructive">Danger Zone</CardTitle>
                    <CardDescription>Irreversible actions</CardDescription>
                </CardHeader>
                <CardContent>
                    <p className="mb-4 text-sm text-muted-foreground">
                        Deleting your business will remove all associated data permanently.
                    </p>
                    <Button variant="destructive">Delete Business</Button>
                </CardContent>
            </Card>
        </div>
    );
}
