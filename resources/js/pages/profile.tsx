import { useRef, useState } from 'react';
import { useAuthStore } from '@/stores/auth-store';
import { useUpdateProfile, useChangePassword, useLogout } from '@/hooks/useAuth';
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
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Spinner } from '@/components/ui/spinner';
import { Skeleton } from '@/components/ui/skeleton';

export default function ProfilePage() {
    const { user, isAuthenticated } = useAuthStore();
    const [name, setName] = useState(user?.name || '');
    const [phone, setPhone] = useState(user?.phone || '');
    const [timezone, setTimezone] = useState(user?.timezone || 'UTC');
    const [currentPassword, setCurrentPassword] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [newPasswordConfirmation, setNewPasswordConfirmation] = useState('');
    const fileInputRef = useRef<HTMLInputElement>(null);

    const updateProfile = useUpdateProfile();
    const changePassword = useChangePassword();
    const logout = useLogout();

    if (!isAuthenticated || !user) {
        return (
            <div className="container mx-auto max-w-2xl space-y-6 py-8">
                <Skeleton className="h-8 w-48" />
                <Skeleton className="h-64 w-full" />
            </div>
        );
    }

    const handleProfileSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        updateProfile.mutate({ name, phone, timezone });
    };

    const handlePasswordSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        changePassword.mutate(
            {
                current_password: currentPassword,
                new_password: newPassword,
                new_password_confirmation: newPasswordConfirmation,
            },
            {
                onSuccess: () => {
                    setCurrentPassword('');
                    setNewPassword('');
                    setNewPasswordConfirmation('');
                },
            },
        );
    };

    const handleAvatarClick = () => {
        fileInputRef.current?.click();
    };

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            updateProfile.mutate({ avatar: file });
        }
    };

    const initials = user.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

    return (
        <div className="container mx-auto max-w-2xl space-y-8 py-8">
            <div>
                <h1 className="text-3xl font-bold">Profile</h1>
                <p className="text-muted-foreground">Manage your account settings</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Profile Information</CardTitle>
                    <CardDescription>Update your personal details</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleProfileSubmit} className="space-y-6">
                        <div className="flex items-center gap-4">
                            <Avatar className="h-16 w-16 cursor-pointer" onClick={handleAvatarClick}>
                                <AvatarImage src={user.avatar || undefined} alt={user.name} />
                                <AvatarFallback>{initials}</AvatarFallback>
                            </Avatar>
                            <div>
                                <Button type="button" variant="outline" size="sm" onClick={handleAvatarClick}>
                                    Change avatar
                                </Button>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/*"
                                    className="hidden"
                                    onChange={handleAvatarChange}
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Email</Label>
                                <Input value={user.email} disabled />
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
                                <Label htmlFor="timezone">Timezone</Label>
                                <Input
                                    id="timezone"
                                    value={timezone}
                                    onChange={(e) => setTimezone(e.target.value)}
                                />
                            </div>
                        </div>

                        {updateProfile.isSuccess && (
                            <p className="text-sm text-green-600 dark:text-green-400">
                                Profile updated successfully
                            </p>
                        )}

                        <Button type="submit" disabled={updateProfile.isPending}>
                            {updateProfile.isPending ? <Spinner className="mr-2" /> : null}
                            Save changes
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Change Password</CardTitle>
                    <CardDescription>Update your password</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handlePasswordSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="current_password">Current Password</Label>
                            <Input
                                id="current_password"
                                type="password"
                                value={currentPassword}
                                onChange={(e) => setCurrentPassword(e.target.value)}
                                required
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="new_password">New Password</Label>
                            <Input
                                id="new_password"
                                type="password"
                                value={newPassword}
                                onChange={(e) => setNewPassword(e.target.value)}
                                required
                                minLength={8}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="new_password_confirmation">Confirm New Password</Label>
                            <Input
                                id="new_password_confirmation"
                                type="password"
                                value={newPasswordConfirmation}
                                onChange={(e) => setNewPasswordConfirmation(e.target.value)}
                                required
                            />
                        </div>

                        {changePassword.isSuccess && (
                            <p className="text-sm text-green-600 dark:text-green-400">
                                Password changed successfully
                            </p>
                        )}
                        {changePassword.error && (
                            <p className="text-sm text-destructive">
                                {changePassword.error instanceof Error
                                    ? changePassword.error.message
                                    : 'Failed to change password'}
                            </p>
                        )}

                        <Button type="submit" disabled={changePassword.isPending}>
                            {changePassword.isPending ? <Spinner className="mr-2" /> : null}
                            Update password
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Sessions</CardTitle>
                    <CardDescription>Manage your active sessions</CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        You are currently signed in. You can sign out from all devices.
                    </p>
                    <Button variant="outline" onClick={() => logout.mutate()} disabled={logout.isPending}>
                        {logout.isPending ? <Spinner className="mr-2" /> : null}
                        Sign out from all devices
                    </Button>
                </CardContent>
            </Card>

            <Separator />

            <Card className="border-destructive/50">
                <CardHeader>
                    <CardTitle className="text-destructive">Danger Zone</CardTitle>
                    <CardDescription>Irreversible actions</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="destructive">Delete account</Button>
                </CardContent>
            </Card>
        </div>
    );
}
