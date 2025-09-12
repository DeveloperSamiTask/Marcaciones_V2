import { Skeleton } from "@/components/ui/skeleton";

interface LoadingSkeletonProps {
  rows?: number;
  rowHeight?: string;
  header?: boolean;
  headerHeight?: string;
  className?: string;
  rowClassName?: string;
  gap?: string;
  rounded?: string;
  variant?: "default" | "card" | "table";
}

export const LoadingSkeleton = ({
  rows = 8,
  rowHeight = "h-6",
  header = true,
  headerHeight = "h-[40px]",
  className = "",
  rowClassName = "bg-gray-200 dark:bg-neutral-800",
  gap = "gap-4 md:gap-6",
  rounded = "rounded-xl",
  variant = "default",
}: LoadingSkeletonProps) => {
  const variants = {
    default: "",
    card: "border bg-card p-4 shadow-sm",
    table: "border divide-y",
  };

  return (
    <div className={`flex h-full flex-col py-4 ${gap} ${rounded} ${variants[variant]} ${className}`}>
      {header && <Skeleton className={`w-full ${rounded} ${headerHeight}`} />}
      <div className="space-y-2">
        {[...Array(rows)].map((_, i) => (
          <Skeleton
            key={i}
            className={`w-full ${rowHeight} ${rounded} ${rowClassName}`}
          />
        ))}
      </div>
    </div>
  );
};
