'use client';

import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useCustomer, useUpdateCustomer } from '@/hooks/useCustomer';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { toast } from 'sonner';
import type {
    CustomerStatus, CustomerSource, CustomerType, LifecycleStage,
} from '@/types/customer';

const statuses: CustomerStatus[] = ['active', 'archived', 'inactive', 'blacklisted'];
const sources: CustomerSource[] = ['manual', 'import', 'whatsapp', 'web_form', 'api', 'referral', 'social', 'other'];
const types: CustomerType[] = ['individual', 'business'];
const stages: LifecycleStage[] = ['lead', 'prospect', 'active', 'at_risk', 'churned', 'vip'];
const timezones = ['UTC', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'Europe/London', 'Europe/Paris', 'Asia/Tokyo', 'Asia/Shanghai', 'Asia/Kolkata'];
const languages = ['en', 'es', 'fr', 'de', 'pt', 'zh', 'ja', 'ar'];

export default function EditCustomerPage() {
    const { uuid } = useParams<{ uuid: string }>();
    const navigate = useNavigate();
    const { data: customer, isLoading, isError, error } = useCustomer(uuid);
    const updateCustomer = useUpdateCustomer(uuid);

    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [email, setEmail] = useState('');
    const [notes, setNotes] = useState('');
    const [status, setStatus] = useState<CustomerStatus>('active');
    const [source, setSource] = useState<CustomerSource>('manual');
    const [customerType, setCustomerType] = useState<CustomerType>('individual');
    const [lifecycleStage, setLifecycleStage] = useState<LifecycleStage>('lead');
    const [companyName, setCompanyName] = useState('');
    const [jobTitle, setJobTitle] = useState('');
    const [timezone, setTimezone] = useState('UTC');
    const [preferredLanguage, setPreferredLanguage] = useState('en');

    useEffect(() => {
        if (!customer) return;
        setName(customer.name || '');
        setPhone(customer.phone || '');
        setEmail(customer.email || '');
        setNotes(customer.notes || '');
        setStatus(customer.status);
        setSource(customer.source);
        setCustomerType(customer.customer_type);
        setLifecycleStage(customer.lifecycle_stage);
        setCompanyName(customer.company_name || '');
        setJobTitle(customer.job_title || '');
        setTimezone(customer.timezone || 'UTC');
        setPreferredLanguage(customer.preferred_language || 'en');
    }, [customer]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!name.trim()) {
            toast.error('Name is required');
            return;
        }
        updateCustomer.mutate(
            {
                name: name.trim(),
                phone: phone || undefined,
                email: email || undefined,
                notes: notes || undefined,
                status,
                source,
                customer_type: customerType,
                lifecycle_stage: lifecycleStage,
                company_name: companyName || undefined,
                job_title: jobTitle || undefined,
                timezone,
                preferred_language: preferredLanguage,
            },
            {
                onSuccess: () => {
                    toast.success('Customer updated');
                    navigate(`/customers/${uuid}`);
                },
                onError: () => {
                    toast.error('Failed to update customer');
                },
            },
        );
    };

    if (isLoading) {
        return (
            <div className="container mx-auto max-w-3xl space-y-6 p-6">
                <Skeleton className="h-6 w-64" />
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-64 w-full" />
                <Skeleton className="h-48 w-full" />
            </div>
        );
    }

    if (isError || !customer) {
        return (
            <div className="container mx-auto max-w-3xl p-6">
                <Card>
                    <CardContent className="flex flex-col items-center py-12">
                        <p className="text-lg font-semibold text-destructive">Failed to load customer</p>
                        <p className="text-sm text-muted-foreground">{(error as Error)?.message || 'Customer not found'}</p>
                        <Button variant="outline" className="mt-4" onClick={() => navigate('/customers')}>
                            Back to Customers
                        </Button>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="container mx-auto max-w-3xl space-y-6 p-6">
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <Link to="/customers">Customers</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <Link to={`/customers/${uuid}`}>{customer.name}</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>Edit</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <div>
                <h1 className="text-3xl font-bold">Edit Customer</h1>
                <p className="text-muted-foreground">Update customer information</p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>Contact details for this customer</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name *</Label>
                            <Input id="name" value={name} onChange={(e) => setName(e.target.value)} required />
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" value={phone} onChange={(e) => setPhone(e.target.value)} />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea id="notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={3} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Classification</CardTitle>
                        <CardDescription>Categorize this customer</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="status">Status</Label>
                            <Select value={status} onValueChange={(v) => setStatus(v as CustomerStatus)}>
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {statuses.map((s) => (
                                        <SelectItem key={s} value={s}>{s.charAt(0).toUpperCase() + s.slice(1)}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="source">Source</Label>
                            <Select value={source} onValueChange={(v) => setSource(v as CustomerSource)}>
                                <SelectTrigger id="source">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {sources.map((s) => (
                                        <SelectItem key={s} value={s}>{s.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="type">Customer Type</Label>
                            <Select value={customerType} onValueChange={(v) => setCustomerType(v as CustomerType)}>
                                <SelectTrigger id="type">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {types.map((t) => (
                                        <SelectItem key={t} value={t}>{t.charAt(0).toUpperCase() + t.slice(1)}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="stage">Lifecycle Stage</Label>
                            <Select value={lifecycleStage} onValueChange={(v) => setLifecycleStage(v as LifecycleStage)}>
                                <SelectTrigger id="stage">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {stages.map((s) => (
                                        <SelectItem key={s} value={s}>{s.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Company</CardTitle>
                        <CardDescription>Professional information</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="company">Company Name</Label>
                            <Input id="company" value={companyName} onChange={(e) => setCompanyName(e.target.value)} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="job_title">Job Title</Label>
                            <Input id="job_title" value={jobTitle} onChange={(e) => setJobTitle(e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Preferences</CardTitle>
                        <CardDescription>Localization preferences</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="timezone">Timezone</Label>
                            <Select value={timezone} onValueChange={setTimezone}>
                                <SelectTrigger id="timezone">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {timezones.map((tz) => (
                                        <SelectItem key={tz} value={tz}>{tz}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="language">Preferred Language</Label>
                            <Select value={preferredLanguage} onValueChange={setPreferredLanguage}>
                                <SelectTrigger id="language">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {languages.map((l) => (
                                        <SelectItem key={l} value={l}>{l.toUpperCase()}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Separator />

                <div className="flex items-center gap-3">
                    <Button type="submit" disabled={updateCustomer.isPending}>
                        {updateCustomer.isPending ? <Spinner className="mr-2" /> : null}
                        Save Changes
                    </Button>
                    <Button type="button" variant="outline" onClick={() => navigate(`/customers/${uuid}`)}>
                        Cancel
                    </Button>
                </div>
            </form>
        </div>
    );
}
