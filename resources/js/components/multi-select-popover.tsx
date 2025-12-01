import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronDown } from 'lucide-react';

type Item = { id: number; label: string };

type Props = {
    selected: number[];
    onChange: (selected: number[]) => void;
    items: Item[];
    placeholder: string;
    searchPlaceholder: string;
    tabIndex?: number;
};

export default function MultiSelectPopover({ selected, onChange, items, placeholder, searchPlaceholder, tabIndex }: Props) {
    const toggleItem = (id: number) => {
        onChange(selected.includes(id) ? selected.filter(i => i !== id) : [...selected, id]);
    };

    return (
        <>
            <Popover>
                <PopoverTrigger asChild>
                    <Button variant="outline" tabIndex={tabIndex} role="combobox" className="bg-card justify-between">
                        {selected.length > 0 ? `${selected.length} seleccionado(s)` : placeholder}
                        <ChevronDown className="opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[600px] p-0">
                    <Command>
                        <CommandInput placeholder={searchPlaceholder} className="h-9" />
                        <CommandList>
                            <CommandEmpty>Sin resultados.</CommandEmpty>
                            <CommandGroup>
                                {items.map((item) => (
                                    <CommandItem key={item.id} value={`${item.id} ${item.label}`} onSelect={() => toggleItem(item.id)}>
                                        {item.label}
                                        <Check className={cn('ml-auto', selected.includes(item.id) ? 'opacity-100' : 'opacity-0')} />
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            {selected.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-2">
                    {selected.map((id) => {
                        const item = items.find((i) => i.id === id);
                        return item ? (
                            <div key={id} className="bg-primary/10 text-primary flex items-center gap-1 rounded-md px-2 py-1 text-sm">
                                {item.label}
                                <button type="button" onClick={() => toggleItem(id)} className="hover:text-destructive ml-1">
                                    ×
                                </button>
                            </div>
                        ) : null;
                    })}
                </div>
            )}
        </>
    );
}
