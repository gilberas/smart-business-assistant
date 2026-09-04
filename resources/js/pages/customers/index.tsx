'use client';

import { useState, useCallback, ChangeEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
    useCustomers,
    useCustomerStats,
    useDeleteCustomer,
    useArchiveCustomer,
    useRestoreCustomer,
    useImportPreview,
    useImportCustomers,
} from '@/hooks/useCustomer';
import { useCustomerStore } from '@/stores/customer-store';
import { Button } from '@/components/ui/button';
import {
    Card, CardContent, CardHeader, CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { format } from '@/lib/date-utils';
import { customerApi } from '@/lib/api/customer';
import {
    Search, MoreHorizontal, Plus, Upload, Download, Eye, Pencil, Archive, Trash2, RotateCcw,
} from 'lucide-react';
import type {
    CustomerFilters,
    CustomerList,
    CustomerStatus,
    CustomerSource,
    CustomerType,
    LifecycleStage,
    ImportRow,
} from '@/types/customer';

const statusOptions: { value: CustomerStatus | 'all'; label: string }[] = [
    { value: 'all', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'archived', label: 'Archived' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'blacklisted', label: 'Blacklisted' },
];

const sourceOptions: { value: CustomerSource | 'all'; label: string }[] = [
    { value: 'all', label: 'All Sources' },
    { value: 'manual', label: 'Manual' },
    { value: 'import', label: 'Import' },
    { value: 'whatsapp', label: 'WhatsApp' },
    { value: 'web_form', label: 'Web Form' },
    { value: 'api', label: 'API' },
    { value: 'referral', label: 'Referral' },
    { value: 'social', label: 'Social' },
    { value: 'other', label: 'Other' },
];

const typeOptions: { value: CustomerType | 'all'; label: string }[] = [
    { value: 'all', label: 'All Types' },
    { value: 'individual', label: 'Individual' },
    { value: 'business', label: 'Business' },
];

const stageOptions: { value: LifecycleStage | 'all'; label: string }[] = [
    { value: 'all', label: 'All Stages' },
    { value: 'lead', label: 'Lead' },
    { value: 'prospect', label: 'Prospect' },
    { value: 'active', label: 'Active' },
    { value: 'at_risk', label: 'At Risk' },
    { value: 'churned', label: 'Churned' },
    { value: 'vip', label: 'VIP' },
];

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    active: 'default',
    archived: 'secondary',
    inactive: 'outline',
    blacklisted: 'destructive',
};

const stageVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    lead: 'secondary',
    prospect: 'outline',
    active: 'default',
    at_risk: 'destructive',
    churned: 'secondary',
    vip: 'default',
};

function StatsCards() {
    const { data: stats, isLoading } = useCustomerStats();

    if (isLoading || !stats) {
        return (
            <div className="grid gap-4 md:grid-cols-4">
                {Array.from({ length: 4 }).map((_, i) => (
                    <Card key={i}>
                        <CardHeader className="pb-2">
                            <Skeleton className="h-4 w-20" />
                        </CardHeader>
                        <CardContent>
                            <Skeleton className="h-8 w-16" />
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Total</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold">{stats.total}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Active</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold text-green-600">{stats.active}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">Archived</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold text-muted-foreground">{stats.archived}</p>
                </CardContent>
            </Card>
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium text-muted-foreground">New</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-2xl font-bold">
                        <span className="text-blue-600">{stats.new_today}</span>
                        <span className="mx-1 text-sm text-muted-foreground">/</span>
                        <span className="text-indigo-600">{stats.new_this_week}</span>
                        <span className="mx-1 text-sm text-muted-foreground">/</span>
                        <span className="text-violet-600">{stats.new_this_month}</span>
                    </p>
                    <p className="text-xs text-muted-foreground">today / week / month</p>
                </CardContent>
            </Card>
        </div>
    );
}

function FilterBar({
    filters,
    onFiltersChange,
}: {
    filters: CustomerFilters;
    onFiltersChange: (filters: CustomerFilters) => void;
}) {
    const setFilter = <K extends keyof CustomerFilters>(key: K, value: CustomerFilters[K]) => {
        onFiltersChange({ ...filters, [key]: value || undefined });
    };

    return (
        <div className="flex flex-wrap items-end gap-3">
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Status</label>
                <Select
                    value={filters.status as string || 'all'}
                    onValueChange={(v) => setFilter('status', v === 'all' ? undefined : (v as CustomerStatus))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statusOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Source</label>
                <Select
                    value={filters.source as string || 'all'}
                    onValueChange={(v) => setFilter('source', v === 'all' ? undefined : (v as CustomerSource))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Source" />
                    </SelectTrigger>
                    <SelectContent>
                        {sourceOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Stage</label>
                <Select
                    value={filters.lifecycle_stage as string || 'all'}
                    onValueChange={(v) => setFilter('lifecycle_stage', v === 'all' ? undefined : (v as LifecycleStage))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Stage" />
                    </SelectTrigger>
                    <SelectContent>
                        {stageOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Type</label>
                <Select
                    value={filters.customer_type || 'all'}
                    onValueChange={(v) => setFilter('customer_type', v === 'all' ? undefined : (v as CustomerType))}
                >
                    <SelectTrigger className="w-36">
                        <SelectValue placeholder="Type" />
                    </SelectTrigger>
                    <SelectContent>
                        {typeOptions.map((o) => (
                            <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">Search</label>
                <div className="relative">
                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Name, phone, email..."
                        value={filters.search || ''}
                        onChange={(e) => setFilter('search', e.target.value || undefined)}
                        className="w-56 pl-8"
                    />
                </div>
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">From</label>
                <Input
                    type="date"
                    value={filters.created_from || ''}
                    onChange={(e) => setFilter('created_from', e.target.value || undefined)}
                    className="w-40"
                />
            </div>
            <div className="space-y-1">
                <label className="text-xs font-medium text-muted-foreground">To</label>
                <Input
                    type="date"
                    value={filters.created_to || ''}
                    onChange={(e) => setFilter('created_to', e.target.value || undefined)}
                    className="w-40"
                />
            </div>
            <Button
                variant="ghost"
                size="sm"
                onClick={() => onFiltersChange({})}
                className="mb-0.5"
            >
                Clear
            </Button>
        </div>
    );
}

export default function CustomerListPage() {
    const navigate = useNavigate();
    const { filters: storeFilters, setFilters, resetFilters } = useCustomerStore();
    const [page, setPage] = useState(1);
    const [localFilters, setLocalFilters] = useState<CustomerFilters>({});
    const [deleteUuid, setDeleteUuid] = useState<string | null>(null);
    const [importOpen, setImportOpen] = useState(false);
    const [csvRows, setCsvRows] = useState<Record<string, unknown>[]>([]);
    const [csvPreview, setCsvPreview] = useState<ImportRow[]>([]);
    const [importErrors, setImportErrors] = useState<Record<number, string[]> | null>(null);
    const [importing, setImporting] = useState(false);

    const effectiveFilters = { ...localFilters, ...storeFilters, page, per_page: 15 };
    const { data, isLoading, isError, error } = useCustomers(effectiveFilters);
    const deleteCustomer = useDeleteCustomer();
    const archiveCustomer = useArchiveCustomer();
    const restoreCustomer = useRestoreCustomer();
    const previewMutation = useImportPreview();
    const importMutation = useImportCustomers();

    const handleExport = useCallback(async () => {
        try {
            const blob = await customerApi.exportCsv(localFilters);
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'customers-export.csv';
            a.click();
            URL.revokeObjectURL(url);
            toast.success('Export started');
        } catch {
            toast.error('Export failed');
        }
    }, [localFilters]);

    const handleFileSelect = useCallback((e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            const text = ev.target?.result as string;
            const lines = text.split('\n').filter(Boolean);
            if (lines.length < 2) {
                toast.error('CSV must have a header row and at least one data row');
                return;
            }
            const headers = lines[0].split(',').map((h) => h.trim().replace(/^"(.*)"$/, '$1'));
            const rows = lines.slice(1).map((line) => {
                const values = line.split(',').map((v) => v.trim().replace(/^"(.*)"$/, '$1'));
                const row: Record<string, unknown> = {};
                headers.forEach((h, i) => {
                    row[h] = values[i] || '';
                });
                return row;
            });
            setCsvRows(rows);
            previewMutation.mutate(rows, {
                onSuccess: (result) => {
                    setCsvPreview(result.preview);
                    setImportErrors(result.errors);
                },
                onError: () => {
                    toast.error('Failed to preview import');
                },
            });
        };
        reader.readAsText(file);
    }, [previewMutation]);

    const handleImport = useCallback(() => {
        setImporting(true);
        importMutation.mutate(csvRows, {
            onSuccess: (result) => {
                toast.success(`Imported ${result.imported} customers${result.skipped ? `, ${result.skipped} skipped` : ''}`);
                setImportOpen(false);
                setCsvRows([]);
                setCsvPreview([]);
                setImportErrors(null);
            },
            onError: () => {
                toast.error('Import failed');
            },
            onSettled: () => {
                setImporting(false);
            },
        });
    }, [importMutation, csvRows]);

    const handleDelete = useCallback(() => {
        if (!deleteUuid) return;
        deleteCustomer.mutate(deleteUuid, {
            onSuccess: () => {
                toast.success('Customer deleted');
                setDeleteUuid(null);
            },
            onError: () => {
                toast.error('Failed to delete customer');
            },
        });
    }, [deleteCustomer, deleteUuid]);

    const handleArchive = useCallback((uuid: string) => {
        archiveCustomer.mutate(uuid, {
            onSuccess: () => toast.success('Customer archived'),
            onError: () => toast.error('Failed to archive customer'),
        });
    }, [archiveCustomer]);

    const handleRestore = useCallback((uuid: string) => {
        restoreCustomer.mutate(uuid, {
            onSuccess: () => toast.success('Customer restored'),
            onError: () => toast.error('Failed to restore customer'),
        });
    }, [restoreCustomer]);

    if (isError) {
        return (
            <div className="container mx-auto p-6">
                <Card>
                    <CardContent className="flex flex-col items-center py-12">
                        <p className="text-destructive text-lg font-semibold">Failed to load customers</p>
                        <p className="text-muted-foreground text-sm">{(error as Error)?.message || 'An unexpected error occurred'}</p>
                        <Button variant="outline" className="mt-4" onClick={() => window.location.reload()}>
                            Retry
                        </Button>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="container mx-auto space-y-6 p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-3xl font-bold">Customers</h1>
                    <p className="text-muted-foreground">Manage your customer relationships</p>
                </div>
                <div className="flex items-center gap-2">
                    <Button variant="outline" onClick={() => setImportOpen(true)}>
                        <Upload className="mr-2 h-4 w-4" />
                        Import
                    </Button>
                    <Button variant="outline" onClick={handleExport}>
                        <Download className="mr-2 h-4 w-4" />
                        Export
                    </Button>
                    <Button onClick={() => navigate('/customers/create')}>
                        <Plus className="mr-2 h-4 w-4" />
                        Create
                    </Button>
                </div>
            </div>

            <StatsCards />

            <FilterBar
                filters={{ ...localFilters, ...storeFilters }}
                onFiltersChange={(f) => {
                    setLocalFilters(f);
                    setPage(1);
                }}
            />

            <Card>
                <CardContent className="p-0">
                    {isLoading ? (
                        <div className="space-y-3 p-4">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">Name</th>
                                        <th className="px-4 py-3 font-medium">Phone</th>
                                        <th className="px-4 py-3 font-medium">Email</th>
                                        <th className="px-4 py-3 font-medium">Status</th>
                                        <th className="px-4 py-3 font-medium">Stage</th>
                                        <th className="px-4 py-3 font-medium">Source</th>
                                        <th className="px-4 py-3 font-medium">Tags</th>
                                        <th className="px-4 py-3 font-medium">Last Interacted</th>
                                        <th className="px-4 py-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data?.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={9} className="px-4 py-12 text-center text-muted-foreground">
                                                No customers found
                                            </td>
                                        </tr>
                                    ) : (
                                        data?.data.map((customer: CustomerList) => (
                                            <tr key={customer.uuid} className="border-b last:border-0 hover:bg-muted/50">
                                                <td className="px-4 py-3">
                                                    <Link
                                                        to={`/customers/${customer.uuid}`}
                                                        className="font-medium text-primary hover:underline"
                                                    >
                                                        {customer.name}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">{customer.phone || '-'}</td>
                                                <td className="px-4 py-3">{customer.email || '-'}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={statusVariant[customer.status] || 'outline'}>
                                                        {customer.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={stageVariant[customer.lifecycle_stage] || 'outline'}>
                                                        {customer.lifecycle_stage.replace('_', ' ')}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 capitalize">{customer.source.replace('_', ' ')}</td>
                                                <td className="px-4 py-3">
                                                    <div className="flex max-w-32 flex-wrap gap-1">
                                                        {customer.tags?.slice(0, 3).map((tag) => (
                                                            <Badge key={tag.id} variant="secondary" className="text-xs">
                                                                {tag.name}
                                                            </Badge>
                                                        ))}
                                                        {(customer.tags?.length || 0) > 3 && (
                                                            <span className="text-xs text-muted-foreground">
                                                                +{customer.tags!.length - 3}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {customer.last_interacted_at
                                                        ? format(new Date(customer.last_interacted_at), 'MMM d, yyyy')
                                                        : '-'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon">
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem onClick={() => navigate(`/customers/${customer.uuid}`)}>
                                                                <Eye className="mr-2 h-4 w-4" />
                                                                View
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem onClick={() => navigate(`/customers/${customer.uuid}/edit`)}>
                                                                <Pencil className="mr-2 h-4 w-4" />
                                                                Edit
                                                            </DropdownMenuItem>
                                                            {customer.status === 'archived' ? (
                                                                <DropdownMenuItem onClick={() => handleRestore(customer.uuid)}>
                                                                    <RotateCcw className="mr-2 h-4 w-4" />
                                                                    Restore
                                                                </DropdownMenuItem>
                                                            ) : (
                                                                <DropdownMenuItem onClick={() => handleArchive(customer.uuid)}>
                                                                    <Archive className="mr-2 h-4 w-4" />
                                                                    Archive
                                                                </DropdownMenuItem>
                                                            )}
                                                            <DropdownMenuItem
                                                                className="text-destructive"
                                                                onClick={() => setDeleteUuid(customer.uuid)}
                                                            >
                                                                <Trash2 className="mr-2 h-4 w-4" />
                                                                Delete
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            {data?.meta && data.meta.last_page > 1 && (
                <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                        Showing {data.meta.from}–{data.meta.to} of {data.meta.total}
                    </p>
                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page <= 1}
                            onClick={() => setPage(page - 1)}
                        >
                            Previous
                        </Button>
                        {Array.from({ length: data.meta.last_page }, (_, i) => i + 1)
                            .filter((p) => p === 1 || p === data.meta.last_page || Math.abs(p - page) <= 2)
                            .map((p, idx, arr) => (
                                <span key={p} className="flex items-center">
                                    {idx > 0 && arr[idx - 1] !== p - 1 && (
                                        <span className="px-1 text-muted-foreground">...</span>
                                    )}
                                    <Button
                                        variant={p === page ? 'default' : 'outline'}
                                        size="sm"
                                        onClick={() => setPage(p)}
                                        className="min-w-9"
                                    >
                                        {p}
                                    </Button>
                                </span>
                            ))}
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={page >= data.meta.last_page}
                            onClick={() => setPage(page + 1)}
                        >
                            Next
                        </Button>
                    </div>
                </div>
            )}

            <Dialog open={!!deleteUuid} onOpenChange={(open) => !open && setDeleteUuid(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Customer</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this customer? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteUuid(null)}>Cancel</Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={deleteCustomer.isPending}
                        >
                            {deleteCustomer.isPending ? <Spinner className="mr-2" /> : null}
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={importOpen} onOpenChange={(open) => { setImportOpen(open); if (!open) { setCsvRows([]); setCsvPreview([]); setImportErrors(null); } }}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Import Customers</DialogTitle>
                        <DialogDescription>
                            Upload a CSV file to import customers. The first row should contain column headers.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <input
                            type="file"
                            accept=".csv"
                            onChange={handleFileSelect}
                            className="block w-full text-sm text-muted-foreground file:mr-4 file:rounded file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:text-primary-foreground"
                        />
                        {previewMutation.isPending && (
                            <div className="flex items-center justify-center py-8">
                                <Spinner />
                            </div>
                        )}
                        {csvPreview.length > 0 && (
                            <div className="max-h-64 overflow-auto rounded border">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/50 text-left">
                                            {Object.keys(csvPreview[0]).map((key) => (
                                                <th key={key} className="px-3 py-2 font-medium">{key}</th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {csvPreview.map((row, i) => (
                                            <tr key={i} className={cn('border-b last:border-0', importErrors?.[i] && 'bg-destructive/10')}>
                                                {Object.values(row).map((val, j) => (
                                                    <td key={j} className="px-3 py-2">{String(val)}</td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        {importErrors && Object.keys(importErrors).length > 0 && (
                            <div className="rounded border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                                {Object.entries(importErrors).map(([row, errs]) => (
                                    <p key={row}>Row {Number(row) + 1}: {errs.join(', ')}</p>
                                ))}
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setImportOpen(false)}>Cancel</Button>
                        <Button onClick={handleImport} disabled={csvRows.length === 0 || importing}>
                            {importing ? <Spinner className="mr-2" /> : null}
                            Import {csvRows.length} rows
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
