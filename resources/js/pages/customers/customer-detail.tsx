'use client';

import { useState, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
    useCustomer,
    useDeleteCustomer,
    useArchiveCustomer,
    useRestoreCustomer,
    useCustomerNotes,
    useCreateCustomerNote,
    useUpdateCustomerNote,
    useDeleteCustomerNote,
    useTogglePinNote,
    useCustomerActivity,
    useTags,
    useAttachTags,
    useDetachTags,
    useMergeCustomers,
} from '@/hooks/useCustomer';
import { customerApi } from '@/lib/api/customer';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Tabs, TabsContent, TabsList, TabsTrigger,
} from '@/components/ui/tabs';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import {
    Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { format, formatDistanceToNow } from '@/lib/date-utils';
import {
    Phone, Mail, Globe, MapPin, Cake, Star, Tag, Plus, Pin, PinOff, Pencil, Trash2, Archive, RotateCcw,
    ChevronDown, ChevronUp, MessageSquare, PhoneCall, UserPlus, ArrowLeftRight, Edit, X,
} from 'lucide-react';
import type { CustomerNote, CustomerActivity, CustomerTag } from '@/types/customer';

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    active: 'default', archived: 'secondary', inactive: 'outline', blacklisted: 'destructive',
};
const stageVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    lead: 'secondary', prospect: 'outline', active: 'default', at_risk: 'destructive', churned: 'secondary', vip: 'default',
};

const activityIcons: Record<string, typeof MessageSquare> = {
    note_created: MessageSquare,
    status_changed: ArrowLeftRight,
    customer_created: UserPlus,
    interaction: PhoneCall,
    email_sent: Mail,
    tag_attached: Tag,
    tag_detached: Tag,
};

function ActivityIcon({ type }: { type: string }) {
    const Icon = activityIcons[type] || MessageSquare;
    return <Icon className="h-4 w-4" />;
}

function ActivityEntry({ activity }: { activity: CustomerActivity }) {
    const [expanded, setExpanded] = useState(false);
    return (
        <div className="flex gap-3">
            <div className="relative flex flex-col items-center">
                <div className="flex h-8 w-8 items-center justify-center rounded-full border bg-background">
                    <ActivityIcon type={activity.type} />
                </div>
                <div className="h-full w-px bg-border" />
            </div>
            <div className="flex-1 pb-6">
                <div className="flex items-start justify-between">
                    <div>
                        <span className="font-medium">{activity.actor?.name || 'System'}</span>
                        <span className="ml-1 text-muted-foreground">{activity.label}</span>
                    </div>
                    <span className="shrink-0 text-xs text-muted-foreground">
                        {formatDistanceToNow(new Date(activity.created_at), { addSuffix: true })}
                    </span>
                </div>
                {activity.metadata && Object.keys(activity.metadata).length > 0 && (
                    <div className="mt-1">
                        <Button variant="ghost" size="sm" className="h-6 px-1 text-xs" onClick={() => setExpanded(!expanded)}>
                            {expanded ? <ChevronUp className="mr-1 h-3 w-3" /> : <ChevronDown className="mr-1 h-3 w-3" />}
                            {expanded ? 'Hide details' : 'Show details'}
                        </Button>
                        {expanded && (
                            <pre className="mt-1 rounded bg-muted p-2 text-xs">
                                {JSON.stringify(activity.metadata, null, 2)}
                            </pre>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

function NoteCard({
    note,
    customerUuid,
    onEdit,
    onDelete,
    onTogglePin,
}: {
    note: CustomerNote;
    customerUuid: string;
    onEdit: (noteId: number, content: string) => void;
    onDelete: (noteId: number) => void;
    onTogglePin: (noteId: number) => void;
}) {
    const [editing, setEditing] = useState(false);
    const [editContent, setEditContent] = useState(note.content);

    const handleSave = () => {
        if (!editContent.trim()) return;
        onEdit(note.id, editContent.trim());
        setEditing(false);
    };

    return (
        <div className={cn('rounded-lg border p-4', note.is_pinned && 'border-primary/30 bg-primary/5')}>
            <div className="mb-2 flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Avatar className="h-6 w-6">
                        <AvatarImage src={note.author?.avatar || undefined} />
                        <AvatarFallback className="text-xs">{note.author?.name?.charAt(0) || '?'}</AvatarFallback>
                    </Avatar>
                    <span className="text-sm font-medium">{note.author?.name || 'Unknown'}</span>
                    <span className="text-xs text-muted-foreground">
                        {formatDistanceToNow(new Date(note.created_at), { addSuffix: true })}
                    </span>
                    {note.is_pinned && (
                        <Badge variant="secondary" className="text-xs">Pinned</Badge>
                    )}
                </div>
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => onTogglePin(note.id)}>
                        {note.is_pinned ? <PinOff className="h-3.5 w-3.5" /> : <Pin className="h-3.5 w-3.5" />}
                    </Button>
                    <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => setEditing(!editing)}>
                        <Pencil className="h-3.5 w-3.5" />
                    </Button>
                    <Button variant="ghost" size="icon" className="h-7 w-7 text-destructive" onClick={() => onDelete(note.id)}>
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>
            {editing ? (
                <div className="space-y-2">
                    <Textarea value={editContent} onChange={(e) => setEditContent(e.target.value)} rows={3} />
                    <div className="flex gap-2">
                        <Button size="sm" onClick={handleSave}>Save</Button>
                        <Button size="sm" variant="ghost" onClick={() => { setEditing(false); setEditContent(note.content); }}>Cancel</Button>
                    </div>
                </div>
            ) : (
                <p className="whitespace-pre-wrap text-sm">{note.content}</p>
            )}
        </div>
    );
}

export default function CustomerDetailPage() {
    const { uuid } = useParams<{ uuid: string }>();
    const navigate = useNavigate();

    const { data: customer, isLoading, isError, error } = useCustomer(uuid);
    const { data: notes, isLoading: notesLoading } = useCustomerNotes(uuid);
    const { data: activity, isLoading: activityLoading } = useCustomerActivity(uuid);
    const { data: allTags } = useTags();

    const deleteCustomer = useDeleteCustomer();
    const archiveCustomer = useArchiveCustomer();
    const restoreCustomer = useRestoreCustomer();
    const createNote = useCreateCustomerNote(uuid);
    const updateNote = useUpdateCustomerNote(uuid);
    const deleteNote = useDeleteCustomerNote(uuid);
    const togglePin = useTogglePinNote(uuid);
    const attachTags = useAttachTags(uuid);
    const detachTags = useDetachTags(uuid);
    const mergeCustomers = useMergeCustomers();

    const [newNoteContent, setNewNoteContent] = useState('');
    const [mergeOpen, setMergeOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<{ uuid: string; name: string; phone: string | null; email: string | null }[]>([]);
    const [selectedDuplicate, setSelectedDuplicate] = useState<{ uuid: string } | null>(null);
    const [tagSelectOpen, setTagSelectOpen] = useState(false);
    const [searching, setSearching] = useState(false);

    const handleAddNote = useCallback(() => {
        if (!newNoteContent.trim()) return;
        createNote.mutate(newNoteContent.trim(), {
            onSuccess: () => {
                setNewNoteContent('');
                toast.success('Note added');
            },
            onError: () => toast.error('Failed to add note'),
        });
    }, [createNote, newNoteContent]);

    const handleEditNote = useCallback((noteId: number, content: string) => {
        updateNote.mutate({ noteId, content }, {
            onSuccess: () => toast.success('Note updated'),
            onError: () => toast.error('Failed to update note'),
        });
    }, [updateNote]);

    const handleDeleteNote = useCallback((noteId: number) => {
        deleteNote.mutate(noteId, {
            onSuccess: () => toast.success('Note deleted'),
            onError: () => toast.error('Failed to delete note'),
        });
    }, [deleteNote]);

    const handleTogglePin = useCallback((noteId: number) => {
        togglePin.mutate(noteId, {
            onError: () => toast.error('Failed to toggle pin'),
        });
    }, [togglePin]);

    const handleArchive = useCallback(() => {
        if (!uuid) return;
        archiveCustomer.mutate(uuid, {
            onSuccess: () => { toast.success('Customer archived'); },
            onError: () => toast.error('Failed to archive customer'),
        });
    }, [archiveCustomer, uuid]);

    const handleRestore = useCallback(() => {
        if (!uuid) return;
        restoreCustomer.mutate(uuid, {
            onSuccess: () => { toast.success('Customer restored'); },
            onError: () => toast.error('Failed to restore customer'),
        });
    }, [restoreCustomer, uuid]);

    const handleDelete = useCallback(() => {
        if (!uuid) return;
        deleteCustomer.mutate(uuid, {
            onSuccess: () => {
                toast.success('Customer deleted');
                navigate('/customers');
            },
            onError: () => toast.error('Failed to delete customer'),
        });
    }, [deleteCustomer, uuid, navigate]);

    const handleMergeSearch = useCallback(async (q: string) => {
        setSearchQuery(q);
        if (!q.trim()) {
            setSearchResults([]);
            return;
        }
        setSearching(true);
        try {
            const results = await customerApi.search(q);
            setSearchResults(results.filter((r) => r.uuid !== uuid).map((r) => ({
                uuid: r.uuid, name: r.name, phone: r.phone, email: r.email,
            })));
        } catch {
            toast.error('Search failed');
        } finally {
            setSearching(false);
        }
    }, [uuid]);

    const handleMerge = useCallback(() => {
        if (!customer || !selectedDuplicate) return;
        mergeCustomers.mutate(
            { primary_id: customer.id, duplicate_id: 0 },
            {
                onSuccess: () => {
                    toast.success('Customers merged');
                    setMergeOpen(false);
                },
                onError: () => toast.error('Merge failed'),
            },
        );
    }, [customer, selectedDuplicate, mergeCustomers]);

    const handleAttachTag = useCallback((tagName: string) => {
        attachTags.mutate([tagName], {
            onSuccess: () => toast.success('Tag attached'),
            onError: () => toast.error('Failed to attach tag'),
        });
    }, [attachTags]);

    const handleDetachTag = useCallback((tagName: string) => {
        detachTags.mutate([tagName], {
            onSuccess: () => toast.success('Tag detached'),
            onError: () => toast.error('Failed to detach tag'),
        });
    }, [detachTags]);

    const sortedNotes = notes
        ? [...notes].sort((a, b) => {
              if (a.is_pinned !== b.is_pinned) return a.is_pinned ? -1 : 1;
              return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
          })
        : [];

    if (isLoading) {
        return (
            <div className="container mx-auto space-y-6 p-6">
                <Skeleton className="h-6 w-64" />
                <Skeleton className="h-32 w-full" />
                <Skeleton className="h-96 w-full" />
            </div>
        );
    }

    if (isError || !customer) {
        return (
            <div className="container mx-auto p-6">
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
        <div className="container mx-auto space-y-6 p-6">
            <Breadcrumb>
                <BreadcrumbList>
                    <BreadcrumbItem>
                        <BreadcrumbLink asChild>
                            <Link to="/customers">Customers</Link>
                        </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem>
                        <BreadcrumbPage>{customer.name}</BreadcrumbPage>
                    </BreadcrumbItem>
                </BreadcrumbList>
            </Breadcrumb>

            <Card>
                <CardContent className="flex items-center justify-between p-6">
                    <div className="flex items-center gap-4">
                        <Avatar className="h-14 w-14">
                            <AvatarImage src={customer.avatar || undefined} />
                            <AvatarFallback className="text-lg">{customer.name.charAt(0)}</AvatarFallback>
                        </Avatar>
                        <div>
                            <h1 className="text-2xl font-bold">{customer.name}</h1>
                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                <Badge variant={statusVariant[customer.status] || 'outline'}>{customer.status}</Badge>
                                <Badge variant={stageVariant[customer.lifecycle_stage] || 'outline'}>
                                    {customer.lifecycle_stage.replace('_', ' ')}
                                </Badge>
                                <Badge variant="secondary">{customer.source.replace('_', ' ')}</Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={() => navigate(`/customers/${uuid}/edit`)}>
                            <Edit className="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                        {customer.status === 'archived' ? (
                            <Button variant="outline" size="sm" onClick={handleRestore}>
                                <RotateCcw className="mr-2 h-4 w-4" />
                                Restore
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" onClick={handleArchive}>
                                <Archive className="mr-2 h-4 w-4" />
                                Archive
                            </Button>
                        )}
                        <Button variant="outline" size="sm" onClick={() => setMergeOpen(true)}>
                            <ArrowLeftRight className="mr-2 h-4 w-4" />
                            Merge
                        </Button>
                        <Button variant="destructive" size="sm" onClick={handleDelete} disabled={deleteCustomer.isPending}>
                            {deleteCustomer.isPending ? <Spinner className="mr-2" /> : <Trash2 className="mr-2 h-4 w-4" />}
                            Delete
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Tabs defaultValue="details">
                <TabsList>
                    <TabsTrigger value="details">Details</TabsTrigger>
                    <TabsTrigger value="notes">Notes{notes && notes.length > 0 ? ` (${notes.length})` : ''}</TabsTrigger>
                    <TabsTrigger value="activity">Activity{activity ? ` (${activity.length})` : ''}</TabsTrigger>
                </TabsList>

                <TabsContent value="details" className="space-y-6 pt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div className="flex items-center gap-2">
                                <Phone className="h-4 w-4 text-muted-foreground" />
                                <span>{customer.phone || '-'}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Mail className="h-4 w-4 text-muted-foreground" />
                                <span>{customer.email || '-'}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Globe className="h-4 w-4 text-muted-foreground" />
                                <span>{customer.timezone || '-'}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-muted-foreground">Lang:</span>
                                <span>{customer.preferred_language?.toUpperCase() || '-'}</span>
                            </div>
                            {customer.company_name && (
                                <div className="flex items-center gap-2">
                                    <span className="text-muted-foreground">Company:</span>
                                    <span>{customer.company_name}</span>
                                </div>
                            )}
                            {customer.job_title && (
                                <div className="flex items-center gap-2">
                                    <span className="text-muted-foreground">Title:</span>
                                    <span>{customer.job_title}</span>
                                </div>
                            )}
                            {customer.birthday && (
                                <div className="flex items-center gap-2">
                                    <Cake className="h-4 w-4 text-muted-foreground" />
                                    <span>{format(new Date(customer.birthday), 'MMM d, yyyy')}</span>
                                </div>
                            )}
                            <div className="flex items-center gap-2">
                                <Star className="h-4 w-4 text-muted-foreground" />
                                <span>{customer.loyalty_points} pts {customer.loyalty_tier ? `(${customer.loyalty_tier})` : ''}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Addresses</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {(!customer.addresses || customer.addresses.length === 0) ? (
                                <p className="text-sm text-muted-foreground">No addresses</p>
                            ) : (
                                customer.addresses.map((addr) => (
                                    <div key={addr.id} className="flex items-start gap-2 rounded-lg border p-3">
                                        <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium">{addr.label}</span>
                                                {addr.is_primary && <Badge variant="default" className="text-xs">Primary</Badge>}
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {addr.street}, {addr.city}{addr.state ? `, ${addr.state}` : ''} {addr.postal_code || ''}
                                            </p>
                                            <p className="text-sm text-muted-foreground">{addr.country}</p>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Tags</CardTitle>
                            <Button variant="outline" size="sm" onClick={() => setTagSelectOpen(true)}>
                                <Plus className="mr-2 h-4 w-4" />
                                Attach Tag
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {customer.tags.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No tags</p>
                            ) : (
                                <div className="flex flex-wrap gap-2">
                                    {customer.tags.map((tag) => (
                                        <Badge
                                            key={tag.id}
                                            variant="secondary"
                                            className="cursor-pointer gap-1"
                                            onClick={() => handleDetachTag(tag.name)}
                                        >
                                            {tag.name}
                                            <X className="ml-1 h-3 w-3" />
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {customer.metadata && Object.keys(customer.metadata).length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Custom Fields</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-2 sm:grid-cols-2">
                                {Object.entries(customer.metadata).map(([key, value]) => (
                                    <div key={key} className="rounded border p-2">
                                        <p className="text-xs text-muted-foreground">{key}</p>
                                        <p className="text-sm">{String(value)}</p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}
                </TabsContent>

                <TabsContent value="notes" className="space-y-4 pt-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="space-y-2">
                                <Textarea
                                    placeholder="Add a note..."
                                    value={newNoteContent}
                                    onChange={(e) => setNewNoteContent(e.target.value)}
                                    rows={3}
                                />
                                <Button onClick={handleAddNote} disabled={!newNoteContent.trim() || createNote.isPending}>
                                    {createNote.isPending ? <Spinner className="mr-2" /> : null}
                                    Save Note
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {notesLoading ? (
                        <div className="space-y-3">
                            {Array.from({ length: 3 }).map((_, i) => (
                                <Skeleton key={i} className="h-24 w-full" />
                            ))}
                        </div>
                    ) : sortedNotes.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">No notes yet</p>
                    ) : (
                        <div className="space-y-3">
                            {sortedNotes.map((note) => (
                                <NoteCard
                                    key={note.id}
                                    note={note}
                                    customerUuid={uuid!}
                                    onEdit={handleEditNote}
                                    onDelete={handleDeleteNote}
                                    onTogglePin={handleTogglePin}
                                />
                            ))}
                        </div>
                    )}
                </TabsContent>

                <TabsContent value="activity" className="space-y-4 pt-4">
                    {activityLoading ? (
                        <div className="space-y-3">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Skeleton key={i} className="h-16 w-full" />
                            ))}
                        </div>
                    ) : !activity || activity.length === 0 ? (
                        <p className="py-8 text-center text-muted-foreground">No activity yet</p>
                    ) : (
                        <div className="space-y-1">
                            {activity.map((entry) => (
                                <ActivityEntry key={entry.id} activity={entry} />
                            ))}
                        </div>
                    )}
                </TabsContent>
            </Tabs>

            <Dialog open={mergeOpen} onOpenChange={setMergeOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Merge Customers</DialogTitle>
                        <DialogDescription>
                            Search for a duplicate customer to merge into this record.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <Input
                            placeholder="Search by name, phone, or email..."
                            value={searchQuery}
                            onChange={(e) => handleMergeSearch(e.target.value)}
                        />
                        {searching && (
                            <div className="flex justify-center py-4">
                                <Spinner />
                            </div>
                        )}
                        {searchResults.length > 0 && (
                            <div className="max-h-48 space-y-1 overflow-auto rounded border">
                                {searchResults.map((r) => (
                                    <button
                                        key={r.uuid}
                                        type="button"
                                        className={cn(
                                            'flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-accent',
                                            selectedDuplicate?.uuid === r.uuid && 'bg-accent',
                                        )}
                                        onClick={() => setSelectedDuplicate(r)}
                                    >
                                        <span className="font-medium">{r.name}</span>
                                        {r.phone && <span className="text-muted-foreground">{r.phone}</span>}
                                        {r.email && <span className="text-muted-foreground">{r.email}</span>}
                                    </button>
                                ))}
                            </div>
                        )}
                        {searchQuery && !searching && searchResults.length === 0 && (
                            <p className="text-sm text-muted-foreground">No results found</p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setMergeOpen(false)}>Cancel</Button>
                        <Button onClick={handleMerge} disabled={!selectedDuplicate || mergeCustomers.isPending}>
                            {mergeCustomers.isPending ? <Spinner className="mr-2" /> : null}
                            Merge
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={tagSelectOpen} onOpenChange={setTagSelectOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Attach Tags</DialogTitle>
                        <DialogDescription>Select tags to attach to this customer</DialogDescription>
                    </DialogHeader>
                    <div className="max-h-60 space-y-1 overflow-auto">
                        {allTags?.map((tag) => {
                            const isAttached = customer.tags.some((t) => t.id === tag.id);
                            return (
                                <button
                                    key={tag.id}
                                    type="button"
                                    className="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm hover:bg-accent"
                                    onClick={() => (isAttached ? handleDetachTag(tag.name) : handleAttachTag(tag.name))}
                                >
                                    <div className="h-3 w-3 rounded-full" style={{ backgroundColor: tag.color || '#888' }} />
                                    <span className="flex-1">{tag.name}</span>
                                    {isAttached && <span className="text-xs text-muted-foreground">attached</span>}
                                </button>
                            );
                        })}
                        {(!allTags || allTags.length === 0) && (
                            <p className="py-4 text-center text-sm text-muted-foreground">No tags available</p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setTagSelectOpen(false)}>Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
