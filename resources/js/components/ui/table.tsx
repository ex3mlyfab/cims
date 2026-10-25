import type { ComponentProps } from 'react';

/*
 * CIMA-041: minimal shadcn/ui-style table primitives styled for data lists
 * used by the administration modules.
 */
function Table({ className, ...props }: ComponentProps<'table'>) {
    return (
        <div className="relative w-full overflow-x-auto">
            <table
                data-slot="table"
                className={`w-full caption-bottom text-sm ${className || ''}`}
                {...props}
            />
        </div>
    );
}

function TableHeader({ className, ...props }: ComponentProps<'thead'>) {
    return (
        <thead
            data-slot="table-header"
            className={`[&_tr]:border-b ${className || ''}`}
            {...props}
        />
    );
}

function TableBody({ className, ...props }: ComponentProps<'tbody'>) {
    return (
        <tbody
            data-slot="table-body"
            className={`[&_tr:last-child]:border-0 ${className || ''}`}
            {...props}
        />
    );
}

function TableRow({ className, ...props }: ComponentProps<'tr'>) {
    return (
        <tr
            data-slot="table-row"
            className={`border-b border-border/50 transition-colors hover:bg-muted/50 ${className || ''}`}
            {...props}
        />
    );
}

function TableHead({ className, ...props }: ComponentProps<'th'>) {
    return (
        <th
            data-slot="table-head"
            className={`h-12 px-3 text-left align-middle font-medium text-muted-foreground ${className || ''}`}
            {...props}
        />
    );
}

function TableCell({ className, ...props }: ComponentProps<'td'>) {
    return (
        <td
            data-slot="table-cell"
            className={`px-3 py-3 align-middle ${className || ''}`}
            {...props}
        />
    );
}

export { Table, TableHeader, TableBody, TableRow, TableHead, TableCell };
