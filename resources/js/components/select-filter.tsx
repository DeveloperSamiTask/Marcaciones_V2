import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Check, ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from './ui/command';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';

interface BaseSelectFilterProps<T> {
    items: T[];
    placeholder?: string;
    className?: string;
    popoverClassName?: string;
    getValue: (item: T) => string | number;
    displayValue: (item: T) => string;
    searchPlaceholder?: string;
    emptyMessage?: string;
    showSearch?: boolean;
    showCheck?: boolean;
    showChevron?: boolean;
    disabled?: boolean;
}

interface SingleSelectFilterProps<T> extends BaseSelectFilterProps<T> {
    multiple?: false;
    selected: string | number | null;
    onSelect: (value: string | number | null) => void;
}

interface MultiSelectFilterProps<T> extends BaseSelectFilterProps<T> {
    multiple: true;
    selected: (string | number)[];
    onSelect: (value: (string | number)[]) => void;
}

type SelectFilterProps<T> = SingleSelectFilterProps<T> | MultiSelectFilterProps<T>;

export function SelectFilter<T>({
    items,
    selected,
    onSelect,
    placeholder = 'Seleccionar',
    className,
    popoverClassName,
    getValue,
    displayValue,
    searchPlaceholder = 'Buscar...',
    emptyMessage = 'No se encontraron resultados.',
    showSearch = true,
    showCheck = true,
    showChevron = true,
    disabled = false,
    multiple = false,
}: SelectFilterProps<T>) {
    const [open, setOpen] = useState(false);

    const handleSelect = (value: string | number) => {

        if (multiple) {
            const currentSelected = selected as (string | number)[];
            const newSelected = currentSelected.includes(value)
                ? currentSelected.filter((item) => item !== value)
                : [...currentSelected, value];
            (onSelect as (value: (string | number)[]) => void)(newSelected);
        } else {
            (onSelect as (value: string | number | null) => void)(
                value === selected ? null : value
            );
            setOpen(false);
        }
    };

    const selectedItem = items.find((item) => getValue(item) == selected);
    const selectedItems = multiple
        ? items.filter((item) => (selected as (string | number)[]).includes(getValue(item)))
        : [];

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'bg-card w-full justify-between overflow-hidden font-normal',
                        !selected && 'text-muted-foreground',
                        className
                    )}
                    disabled={disabled}
                >
                    <span className="truncate">
                        {multiple
                            ? selectedItems.length > 0
                                ? `${selectedItems.length} seleccionados`
                                : placeholder
                            : selectedItem
                                ? displayValue(selectedItem)
                                : placeholder}
                    </span>
                    {showChevron && (
                        <ChevronDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className={cn(
                    'w-[var(--radix-popover-trigger-width)] p-0',
                    popoverClassName
                )}
                align="start"
            >
                <Command>
                    {showSearch && (
                        <CommandInput
                            placeholder={searchPlaceholder}
                            className="h-9"
                        />
                    )}
                    <CommandList>
                        <CommandEmpty>{emptyMessage}</CommandEmpty>
                        <CommandGroup>
                            {items.map((item) => {
                                const itemValue = getValue(item);
                                const isSelected = multiple
                                    ? (selected as (string | number)[]).includes(itemValue)
                                    : itemValue === selected;

                                return (
                                    <CommandItem
                                        key={itemValue}
                                        value={displayValue(item)}
                                        onSelect={() => handleSelect(itemValue)}
                                    >
                                        {displayValue(item)}
                                        {showCheck && (
                                            <Check
                                                className={cn(
                                                    'ml-auto h-4 w-4',
                                                    isSelected ? 'opacity-100' : 'opacity-0'
                                                )}
                                            />
                                        )}
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
