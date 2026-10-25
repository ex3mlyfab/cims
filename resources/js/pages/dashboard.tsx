import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowUpRight,
    BedDouble,
    Building2,
    CalendarDays,
    ClipboardPlus,
    Clock3,
    UserRound,
    UsersRound,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type DashboardStats = {
    activeInpatients: number | null;
    admissionsToday: number | null;
    patientRegistryCount: number | null;
    installedBeds: number | null;
    occupiedBeds: number | null;
    availableBeds: number | null;
    bedsBeingServiced: number | null;
    bedOccupancyRate: number | null;
};

type WardOccupancy = {
    id: string;
    code: string;
    name: string;
    capacity: number;
    installedBeds: number;
    occupiedBeds: number;
    availableBeds: number;
    cleaningBeds: number;
    outOfServiceBeds: number;
};

type RecentAdmission = {
    id: string;
    patient: { fullname: string; code: string };
    ward: { code: string; name: string };
    arrivedAt: string | null;
    arrivedLabel: string | null;
};

const numberFormatter = new Intl.NumberFormat('en-US');

export default function Dashboard({
    stats,
    wards,
    recentAdmissions,
}: {
    stats: DashboardStats;
    wards: WardOccupancy[];
    recentAdmissions: RecentAdmission[];
}) {
    const { permissions } = usePage().props;
    const canViewPatients = permissions.includes('patients.view');
    const canManageEncounters = permissions.includes('encounters.manage');
    const canViewBeds = permissions.includes('beds.view');
    const canViewWards = permissions.includes('wards.view');
    const canManageWards = permissions.includes('wards.manage');
    const canViewRecentAdmissions = canViewPatients && canManageEncounters;

    const primaryCount = stats.activeInpatients ?? stats.occupiedBeds ?? 0;
    const primaryLabel =
        stats.activeInpatients !== null ? 'Active inpatients' : 'Occupied beds';
    const hasPrimaryMetric =
        stats.activeInpatients !== null || stats.occupiedBeds !== null;
    const hasAnyMetrics =
        hasPrimaryMetric ||
        stats.admissionsToday !== null ||
        stats.patientRegistryCount !== null ||
        stats.installedBeds !== null;

    const summaryCards = [
        stats.admissionsToday !== null && {
            label: 'Admissions today',
            value: stats.admissionsToday,
            note: 'Arrivals recorded today',
            icon: CalendarDays,
        },
        stats.availableBeds !== null && {
            label: 'Beds available',
            value: stats.availableBeds,
            note: `${stats.bedsBeingServiced ?? 0} being serviced`,
            icon: BedDouble,
        },
        stats.patientRegistryCount !== null && {
            label: 'Patient registry',
            value: stats.patientRegistryCount,
            note: 'Registered patients',
            icon: UsersRound,
        },
    ].filter((card) => card !== false);

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col gap-8 p-5 sm:p-7 lg:p-8">
                <header className="flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <div className="mb-3 flex items-center gap-2 text-xs font-semibold tracking-[0.16em] text-primary uppercase">
                            <Activity className="size-4" aria-hidden="true" />
                            Clinical operations
                        </div>
                        <h1 className="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                            Hospital overview
                        </h1>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                            Current patient arrivals, ward occupancy, and bed
                            availability across your hospital.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canViewPatients && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/patients">
                                    <UsersRound aria-hidden="true" />
                                    Patient registry
                                </Link>
                            </Button>
                        )}
                        {canManageEncounters && (
                            <Button size="sm" asChild>
                                <Link href="/encounters/create">
                                    <ClipboardPlus aria-hidden="true" />
                                    New admission
                                </Link>
                            </Button>
                        )}
                    </div>
                </header>

                {hasAnyMetrics ? (
                    <section
                        aria-label="Hospital at a glance"
                        className="grid gap-4 xl:grid-cols-12"
                    >
                        {hasPrimaryMetric && (
                            <div className="relative overflow-hidden rounded-2xl bg-primary p-6 text-primary-foreground shadow-sm sm:p-8 xl:col-span-7">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium text-primary-foreground/75">
                                            {primaryLabel}
                                        </p>
                                        <p className="mt-4 text-5xl leading-none font-semibold tracking-tight tabular-nums sm:text-6xl">
                                            {numberFormatter.format(
                                                primaryCount,
                                            )}
                                        </p>
                                        <p className="mt-3 text-sm text-primary-foreground/75">
                                            {stats.activeInpatients !== null
                                                ? 'Patients currently admitted'
                                                : 'Beds currently occupied'}
                                        </p>
                                    </div>
                                    <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-foreground/10">
                                        <Activity
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </div>

                                {stats.bedOccupancyRate !== null && (
                                    <div className="mt-8 border-t border-primary-foreground/15 pt-5">
                                        <div className="mb-2 flex items-center justify-between gap-3 text-sm">
                                            <span className="text-primary-foreground/75">
                                                Bed occupancy
                                            </span>
                                            <span className="font-semibold tabular-nums">
                                                {stats.bedOccupancyRate}%{' '}
                                                <span className="font-normal text-primary-foreground/65">
                                                    of{' '}
                                                    {numberFormatter.format(
                                                        stats.installedBeds ??
                                                            0,
                                                    )}{' '}
                                                    installed beds
                                                </span>
                                            </span>
                                        </div>
                                        <div
                                            className="h-2 overflow-hidden rounded-full bg-primary-foreground/15"
                                            role="progressbar"
                                            aria-label="Bed occupancy"
                                            aria-valuemin={0}
                                            aria-valuemax={100}
                                            aria-valuenow={
                                                stats.bedOccupancyRate
                                            }
                                        >
                                            <div
                                                className="h-full rounded-full bg-primary-foreground transition-[width] duration-300"
                                                style={{
                                                    width: `${stats.bedOccupancyRate}%`,
                                                }}
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {summaryCards.length > 0 && (
                            <div
                                className={`grid gap-4 sm:grid-cols-2 ${
                                    hasPrimaryMetric
                                        ? 'xl:col-span-5 xl:grid-cols-1'
                                        : 'xl:col-span-12 xl:grid-cols-3'
                                }`}
                            >
                                {summaryCards.map((card) => {
                                    const Icon = card.icon;

                                    return (
                                        <article
                                            key={card.label}
                                            className="flex min-h-32 items-center justify-between gap-4 rounded-2xl border border-border/70 bg-card p-5 shadow-sm"
                                        >
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">
                                                    {card.label}
                                                </p>
                                                <p className="mt-2 text-3xl font-semibold tracking-tight text-foreground tabular-nums">
                                                    {numberFormatter.format(
                                                        card.value,
                                                    )}
                                                </p>
                                                <p className="mt-1 text-xs text-muted-foreground">
                                                    {card.note}
                                                </p>
                                            </div>
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-secondary text-secondary-foreground">
                                                <Icon
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        )}
                    </section>
                ) : (
                    <section className="rounded-2xl border border-border/70 bg-card px-6 py-10 text-center shadow-sm">
                        <Activity
                            className="mx-auto size-8 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <h2 className="mt-4 text-lg font-semibold">
                            No operational metrics available
                        </h2>
                        <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                            Your current role doesn’t include access to
                            admissions or bed metrics. Contact an administrator
                            if you need access.
                        </p>
                    </section>
                )}

                <div className="grid items-start gap-5 xl:grid-cols-12">
                    {canViewBeds && (
                        <section
                            className={`overflow-hidden rounded-2xl border border-border/70 bg-card shadow-sm ${
                                canViewRecentAdmissions
                                    ? 'xl:col-span-7'
                                    : 'xl:col-span-12'
                            }`}
                        >
                            <div className="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
                                <div>
                                    <h2 className="text-lg font-semibold tracking-tight">
                                        Ward occupancy
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Occupied beds against installed beds
                                    </p>
                                </div>
                                <Link
                                    href={canViewWards ? '/wards' : '/beds'}
                                    className="inline-flex min-h-10 items-center gap-1 rounded-md px-2 text-sm font-medium text-primary transition-colors hover:bg-accent/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                >
                                    {canViewWards ? 'View wards' : 'View beds'}
                                    <ArrowUpRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </div>

                            {wards.length > 0 ? (
                                <div className="mt-3 divide-y divide-border/60">
                                    {wards.map((ward) => {
                                        const occupancy =
                                            ward.installedBeds > 0
                                                ? Math.round(
                                                      (ward.occupiedBeds /
                                                          ward.installedBeds) *
                                                          100,
                                                  )
                                                : 0;
                                        const offlineBeds =
                                            ward.cleaningBeds +
                                            ward.outOfServiceBeds;

                                        return (
                                            <article
                                                key={ward.id}
                                                className="px-5 py-5 sm:px-6"
                                            >
                                                <div className="flex flex-wrap items-start justify-between gap-2">
                                                    <div className="flex items-start gap-3">
                                                        <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
                                                            <Building2
                                                                className="size-4"
                                                                aria-hidden="true"
                                                            />
                                                        </div>
                                                        <div>
                                                            <h3 className="font-semibold">
                                                                {ward.name}
                                                            </h3>
                                                            <p className="mt-0.5 text-xs text-muted-foreground">
                                                                {ward.code}
                                                                <span className="px-1.5">
                                                                    ·
                                                                </span>
                                                                {numberFormatter.format(
                                                                    ward.installedBeds,
                                                                )}{' '}
                                                                installed
                                                                <span className="px-1.5">
                                                                    ·
                                                                </span>
                                                                Capacity{' '}
                                                                {numberFormatter.format(
                                                                    ward.capacity,
                                                                )}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <p className="text-sm font-semibold tabular-nums">
                                                        {ward.occupiedBeds}
                                                        <span className="font-normal text-muted-foreground">
                                                            {' '}
                                                            /{' '}
                                                            {
                                                                ward.installedBeds
                                                            }{' '}
                                                            occupied
                                                        </span>
                                                    </p>
                                                </div>

                                                {ward.installedBeds > 0 ? (
                                                    <div
                                                        className="mt-4 flex h-2 overflow-hidden rounded-full bg-muted"
                                                        role="img"
                                                        aria-label={`${ward.occupiedBeds} occupied, ${ward.availableBeds} available, ${offlineBeds} being serviced or out of service`}
                                                    >
                                                        <div
                                                            className="bg-primary"
                                                            style={{
                                                                width: `${(ward.occupiedBeds / ward.installedBeds) * 100}%`,
                                                            }}
                                                        />
                                                        <div
                                                            className="bg-success"
                                                            style={{
                                                                width: `${(ward.availableBeds / ward.installedBeds) * 100}%`,
                                                            }}
                                                        />
                                                        <div
                                                            className="bg-warning"
                                                            style={{
                                                                width: `${(ward.cleaningBeds / ward.installedBeds) * 100}%`,
                                                            }}
                                                        />
                                                        <div
                                                            className="bg-destructive"
                                                            style={{
                                                                width: `${(ward.outOfServiceBeds / ward.installedBeds) * 100}%`,
                                                            }}
                                                        />
                                                    </div>
                                                ) : (
                                                    <p className="mt-4 rounded-lg bg-muted/70 px-3 py-2 text-xs text-muted-foreground">
                                                        No beds installed in
                                                        this ward yet.
                                                    </p>
                                                )}

                                                <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <span className="size-2 rounded-full bg-primary" />
                                                        {ward.occupiedBeds}{' '}
                                                        occupied
                                                    </span>
                                                    <span className="inline-flex items-center gap-1.5">
                                                        <span className="size-2 rounded-full bg-success" />
                                                        {ward.availableBeds}{' '}
                                                        available
                                                    </span>
                                                    {offlineBeds > 0 && (
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <span className="size-2 rounded-full bg-warning" />
                                                            {offlineBeds}{' '}
                                                            offline
                                                        </span>
                                                    )}
                                                    <span className="ml-auto tabular-nums">
                                                        {occupancy}% occupied
                                                    </span>
                                                </div>
                                            </article>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="px-6 py-12 text-center">
                                    <BedDouble
                                        className="mx-auto size-8 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <p className="mt-3 font-medium">
                                        No ward occupancy to show
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Add wards and beds to see capacity here.
                                    </p>
                                    {canManageWards && (
                                        <Button
                                            className="mt-4"
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href="/wards">
                                                Manage wards
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            )}
                        </section>
                    )}

                    {canViewRecentAdmissions && (
                        <section
                            className={`overflow-hidden rounded-2xl border border-border/70 bg-card shadow-sm ${
                                canViewBeds ? 'xl:col-span-5' : 'xl:col-span-12'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
                                <div>
                                    <h2 className="text-lg font-semibold tracking-tight">
                                        Recent admissions
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Latest active arrivals
                                    </p>
                                </div>
                                <Link
                                    href="/encounters"
                                    aria-label="View all admissions"
                                    className="flex size-10 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                >
                                    <ArrowUpRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </div>

                            {recentAdmissions.length > 0 ? (
                                <ul className="mt-3 divide-y divide-border/60">
                                    {recentAdmissions.map((admission) => (
                                        <li
                                            key={admission.id}
                                            className="flex items-center gap-3 px-5 py-4 sm:px-6"
                                        >
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-secondary text-sm font-semibold text-secondary-foreground">
                                                {admission.patient.fullname
                                                    .split(/\s+/)
                                                    .slice(0, 2)
                                                    .map((part) =>
                                                        part.charAt(0),
                                                    )
                                                    .join('')
                                                    .toUpperCase()}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium">
                                                    {admission.patient.fullname}
                                                </p>
                                                <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                                    {admission.patient.code}
                                                    <span className="px-1.5">
                                                        ·
                                                    </span>
                                                    {admission.ward.code}
                                                    {' — '}
                                                    {admission.ward.name}
                                                </p>
                                            </div>
                                            <time
                                                className="inline-flex shrink-0 items-center gap-1.5 text-xs text-muted-foreground tabular-nums"
                                                dateTime={
                                                    admission.arrivedAt ??
                                                    undefined
                                                }
                                            >
                                                <Clock3
                                                    className="size-3.5"
                                                    aria-hidden="true"
                                                />
                                                {admission.arrivedLabel ?? '—'}
                                            </time>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div className="px-6 py-12 text-center">
                                    <UserRound
                                        className="mx-auto size-8 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <p className="mt-3 font-medium">
                                        No active admissions yet
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        New arrivals will appear here.
                                    </p>
                                </div>
                            )}
                        </section>
                    )}

                    {!canViewBeds &&
                        !canViewRecentAdmissions &&
                        (stats.activeInpatients !== null ||
                            stats.admissionsToday !== null) && (
                            <section className="rounded-2xl border border-border/70 bg-card p-6 shadow-sm xl:col-span-12">
                                <div className="flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-secondary text-secondary-foreground">
                                        <Activity
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <div>
                                        <h2 className="font-semibold">
                                            Operational summary
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Counts reflect the latest records
                                            available to your role.
                                        </p>
                                    </div>
                                </div>
                            </section>
                        )}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
