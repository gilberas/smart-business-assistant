import { Check, ChevronsUpDown, PlusCircle, Building2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Spinner } from '@/components/ui/spinner';
import { useSwitchBusiness, useBusinesses } from '@/hooks/useBusiness';
import { cn } from '@/lib/utils';
import { useBusinessStore } from '@/stores/business-store';

interface BusinessSwitcherProps {
    onAddBusiness?: () => void;
}

export function BusinessSwitcher({ onAddBusiness }: BusinessSwitcherProps) {
    const [open, setOpen] = useState(false);
    const { currentBusiness, businesses, isLoading } = useBusinessStore();
    const switchBusiness = useSwitchBusiness();
    useBusinesses();

    const handleSelect = (businessId: number) => {
        if (businessId !== currentBusiness?.id) {
            switchBusiness.mutate(businessId);
        }

        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between"
                    disabled={isLoading || switchBusiness.isPending}
                >
                    {isLoading ? (
                        <Spinner className="mr-2 h-4 w-4" />
                    ) : (
                        <Building2 className="mr-2 h-4 w-4 shrink-0" />
                    )}
                    {currentBusiness?.name ?? 'Select business...'}
                    <ChevronsUpDown className="ml-auto h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[280px] p-0">
                <Command>
                    <CommandInput placeholder="Search business..." />
                    <CommandList>
                        <CommandEmpty>No businesses found.</CommandEmpty>
                        <CommandGroup heading="Your Businesses">
                            {businesses.map((business) => (
                                <CommandItem
                                    key={business.id}
                                    value={business.name}
                                    onSelect={() => handleSelect(business.id)}
                                >
                                    <Building2 className="mr-2 h-4 w-4" />
                                    <span>{business.name}</span>
                                    <Check
                                        className={cn(
                                            'ml-auto h-4 w-4',
                                            currentBusiness?.id === business.id
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                </CommandItem>
                            ))}
                        </CommandGroup>
                        <CommandSeparator />
                        <CommandGroup>
                            <CommandItem
                                onSelect={() => {
                                    setOpen(false);
                                    onAddBusiness?.();
                                }}
                            >
                                <PlusCircle className="mr-2 h-4 w-4" />
                                Create Business
                            </CommandItem>
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
