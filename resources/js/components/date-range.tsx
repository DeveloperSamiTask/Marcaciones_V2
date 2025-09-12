import { CalendarIcon, ChevronDown } from "lucide-react";
import { Button } from "./ui/button";
import { Popover, PopoverContent, PopoverTrigger } from "./ui/popover";
import { Calendar } from "./ui/calendar";
import { format } from "date-fns";
import { es } from "date-fns/locale"
import { cn } from "@/lib/utils";
import { DateRange } from "react-day-picker";

interface DateRangeFilterProps {
  dateRange?: DateRange;
  setDateRange: (range?: DateRange) => void;
  className?: string;
  align?: "start" | "end" | "center";
  numberOfMonths?: number;
  placeholder?: string;
  showIcon?: boolean;
  showChevron?: boolean;
}

export const DateRangeFilter = ({
  dateRange,
  setDateRange,
  className,
  align = "start",
  numberOfMonths = 1,
  placeholder = "SELECCIONAR RANGO DE FECHAS",
  showIcon = true,
  showChevron = false,
}: DateRangeFilterProps) => {
  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          className={cn(
            "bg-card w-full pl-3 text-left font-normal",
            !dateRange && "text-muted-foreground",
            className
          )}
        >
          {dateRange?.from ? (
            dateRange.to ? (
              <>
                {format(dateRange.from, "dd/MM/yyyy")} a {format(dateRange.to, "dd/MM/yyyy")}
              </>
            ) : (
              format(dateRange.from, "dd/MM/yyyy")
            )
          ) : (
            <span>{placeholder}</span>
          )}
          {showIcon && <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />}
          {showChevron && <ChevronDown className="ml-2 h-4 w-4 opacity-50" />}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align={align}>
        <Calendar
          locale={es}
          mode="range"
          defaultMonth={dateRange?.from}
          selected={dateRange}
          onSelect={setDateRange}
          numberOfMonths={numberOfMonths}
          captionLayout="dropdown"
        />
      </PopoverContent>
    </Popover>
  );
};
