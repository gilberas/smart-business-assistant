import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
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
import { Spinner } from '@/components/ui/spinner';
import {
    useCompleteOnboarding,
    useCreateBusiness,
    useUpdateBusiness,
    useUpdateOnboardingStep,
} from '@/hooks/useBusiness';
import { businessApi } from '@/lib/api/business';
import type { Business, OnboardingStep } from '@/types/business';

interface OnboardingWizardProps {
    onComplete?: () => void;
    business?: Business | null;
}

const STEP_SLUGS: OnboardingStep[] = ['business-info', 'details', 'preferences'];
const STEP_LABELS = ['Business Info', 'Details', 'Preferences'];

export function OnboardingWizard({ onComplete, business }: OnboardingWizardProps) {
    const initialIndex = business
        ? Math.max(0, STEP_SLUGS.indexOf(business.onboarding_step as OnboardingStep))
        : 0;

    const [step, setStep] = useState(initialIndex);
    const [name, setName] = useState(business?.name ?? '');
    const [email, setEmail] = useState(business?.email ?? '');
    const [phone, setPhone] = useState(business?.phone ?? '');
    const [website, setWebsite] = useState(business?.website ?? '');
    const [industry, setIndustry] = useState(business?.industry ?? '');
    const [timezone, setTimezone] = useState(
        business?.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone,
    );
    const [locale, setLocale] = useState(business?.locale ?? 'en');
    const [currency, setCurrency] = useState(business?.currency ?? 'USD');
    const [employeeCount, setEmployeeCount] = useState(
        business?.employee_count != null ? String(business.employee_count) : '',
    );

    const createBusiness = useCreateBusiness();
    const updateBusiness = useUpdateBusiness();
    const updateOnboardingStep = useUpdateOnboardingStep(business?.id ?? null);
    const completeOnboarding = useCompleteOnboarding(business?.id ?? null);

    const submitError =
        createBusiness.error ?? updateBusiness.error ?? updateOnboardingStep.error ?? null;

    const goNext = () => {
        const nextStep = step + 1;
        setStep(nextStep);

        if (business) {
            updateOnboardingStep.mutate(STEP_SLUGS[nextStep]);
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        const payload = {
            name,
            email: email || undefined,
            phone: phone || undefined,
            website: website || undefined,
            industry: industry || undefined,
            timezone,
            locale,
            currency,
            employee_count: employeeCount ? parseInt(employeeCount, 10) : undefined,
        };

        try {
            if (business) {
                await updateBusiness.mutateAsync({ id: business.id, payload });
                await completeOnboarding.mutateAsync();
            } else {
                const created = await createBusiness.mutateAsync({
                    ...payload,
                    onboarding_step: STEP_SLUGS[STEP_SLUGS.length - 1],
                });
                await businessApi.completeOnboarding(created.id);
            }

            onComplete?.();
        } catch {
            // error is surfaced via the mutations above
        }
    };

    return (
        <Card className="mx-auto max-w-lg">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-xl">
                        {business ? 'Finish Setting Up Your Business' : 'Create Your Business'}
                    </CardTitle>
                    <span className="text-sm text-muted-foreground">
                        Step {step + 1} of {STEP_SLUGS.length}
                    </span>
                </div>
                <CardDescription>
                    {STEP_LABELS[step] === 'Business Info' && 'Tell us about your business'}
                    {STEP_LABELS[step] === 'Details' && 'Add contact details'}
                    {STEP_LABELS[step] === 'Preferences' && 'Set your preferences'}
                </CardDescription>
            </CardHeader>
            <form onSubmit={handleSubmit}>
                <CardContent className="space-y-4">
                    {submitError && (
                        <div className="rounded-md bg-destructive/10 p-3 text-sm text-destructive">
                            {submitError instanceof Error ? submitError.message : 'Failed to save'}
                        </div>
                    )}

                    {step === 0 && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="name">Business Name *</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Acme Inc."
                                    required
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
                                        <SelectItem value="education">Education</SelectItem>
                                        <SelectItem value="real-estate">Real Estate</SelectItem>
                                        <SelectItem value="hospitality">Hospitality</SelectItem>
                                        <SelectItem value="other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="employee_count">Employee Count</Label>
                                <Input
                                    id="employee_count"
                                    type="number"
                                    value={employeeCount}
                                    onChange={(e) => setEmployeeCount(e.target.value)}
                                    placeholder="50"
                                />
                            </div>
                        </>
                    )}

                    {step === 1 && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="email">Business Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="contact@acme.com"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    placeholder="+1 (555) 000-0000"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="website">Website</Label>
                                <Input
                                    id="website"
                                    type="url"
                                    value={website}
                                    onChange={(e) => setWebsite(e.target.value)}
                                    placeholder="https://acme.com"
                                />
                            </div>
                        </>
                    )}

                    {step === 2 && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="timezone">Timezone</Label>
                                <Select value={timezone} onValueChange={setTimezone}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="UTC">UTC</SelectItem>
                                        <SelectItem value="America/New_York">
                                            Eastern (US)
                                        </SelectItem>
                                        <SelectItem value="America/Chicago">
                                            Central (US)
                                        </SelectItem>
                                        <SelectItem value="America/Denver">
                                            Mountain (US)
                                        </SelectItem>
                                        <SelectItem value="America/Los_Angeles">
                                            Pacific (US)
                                        </SelectItem>
                                        <SelectItem value="Europe/London">London</SelectItem>
                                        <SelectItem value="Europe/Berlin">Berlin</SelectItem>
                                        <SelectItem value="Asia/Tokyo">Tokyo</SelectItem>
                                        <SelectItem value="Asia/Shanghai">Shanghai</SelectItem>
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
                            <div className="space-y-2">
                                <Label htmlFor="currency">Currency</Label>
                                <Select value={currency} onValueChange={setCurrency}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="USD">USD ($)</SelectItem>
                                        <SelectItem value="EUR">EUR (€)</SelectItem>
                                        <SelectItem value="GBP">GBP (£)</SelectItem>
                                        <SelectItem value="BRL">BRL (R$)</SelectItem>
                                        <SelectItem value="JPY">JPY (¥)</SelectItem>
                                        <SelectItem value="TZS">TZS (TSh)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </>
                    )}
                </CardContent>
                <CardFooter className="flex justify-between">
                    {step > 0 ? (
                        <Button type="button" variant="outline" onClick={() => setStep(step - 1)}>
                            Back
                        </Button>
                    ) : (
                        <div />
                    )}
                    {step < STEP_SLUGS.length - 1 ? (
                        <Button type="button" onClick={goNext}>
                            Next
                        </Button>
                    ) : (
                        <Button
                            type="submit"
                            disabled={
                                !name ||
                                createBusiness.isPending ||
                                updateBusiness.isPending ||
                                updateOnboardingStep.isPending
                            }
                        >
                            {createBusiness.isPending || updateBusiness.isPending ? (
                                <Spinner className="mr-2" />
                            ) : null}
                            {business ? 'Finish Setup' : 'Create Business'}
                        </Button>
                    )}
                </CardFooter>
            </form>
        </Card>
    );
}
