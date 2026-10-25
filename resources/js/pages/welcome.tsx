import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowRight,
    ArrowUpRight,
    Clock3,
    HeartPulse,
    MapPin,
    Radio,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

type PublicWard = {
    id: string;
    code: string;
    name: string;
    displayUrl: string;
};

export default function Welcome({ wards }: { wards: PublicWard[] }) {
    const { auth, name, branding } = usePage().props;

    return (
        <>
            <Head title={`${name} | Hospital information`}>
                <meta
                    head-key="description"
                    name="description"
                    content={`Find public ward displays and hospital information for ${name}.`}
                />
            </Head>

            <div className="min-h-screen overflow-hidden bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-5 sm:px-8 lg:px-10">
                    <Link
                        href="/"
                        className="flex min-h-11 items-center gap-3 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                        aria-label={`${name} home`}
                    >
                        {branding?.logoUrl ? (
                            <img
                                src={branding.logoUrl}
                                alt=""
                                className="size-10 rounded-xl border border-border/70 bg-card object-contain p-1"
                            />
                        ) : (
                            <span className="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                                <AppLogoIcon
                                    className="size-6 fill-current"
                                    aria-hidden="true"
                                />
                            </span>
                        )}
                        <span className="max-w-56 truncate text-sm font-semibold tracking-tight sm:text-base">
                            {name}
                        </span>
                    </Link>

                    <nav
                        aria-label="Main navigation"
                        className="flex items-center gap-2"
                    >
                        <a
                            href="#ward-directory"
                            className="hidden min-h-11 items-center rounded-md px-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring sm:inline-flex"
                        >
                            Ward displays
                        </a>
                        {auth.user ? (
                            <Button asChild>
                                <Link href={dashboard()}>
                                    Staff dashboard
                                    <ArrowUpRight aria-hidden="true" />
                                </Link>
                            </Button>
                        ) : (
                            <Button variant="outline" asChild>
                                <Link href={login()}>Staff log in</Link>
                            </Button>
                        )}
                    </nav>
                </header>

                <main>
                    <section className="relative isolate mx-auto grid w-full max-w-7xl gap-12 px-5 pt-10 pb-16 sm:px-8 sm:pt-16 sm:pb-20 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:gap-16 lg:px-10 lg:pt-20 lg:pb-28">
                        <div className="absolute top-0 -right-28 -z-10 size-96 rounded-full bg-primary/5 blur-3xl" />
                        <div>
                            <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1.5 text-xs font-semibold tracking-wide text-primary">
                                <HeartPulse
                                    className="size-3.5"
                                    aria-hidden="true"
                                />
                                HOSPITAL INFORMATION
                            </div>
                            <h1 className="max-w-2xl text-4xl leading-[1.08] font-semibold tracking-[-0.04em] text-balance text-foreground sm:text-5xl lg:text-6xl">
                                The right information,{' '}
                                <span className="text-primary">
                                    right where you need it.
                                </span>
                            </h1>
                            <p className="mt-6 max-w-xl text-base leading-7 text-muted-foreground sm:text-lg sm:leading-8">
                                Find your way to a ward’s public display, or
                                access hospital operations if you’re part of the
                                care team.
                            </p>
                            <div className="mt-8 flex flex-wrap items-center gap-3">
                                <Button size="lg" asChild>
                                    <a href="#ward-directory">
                                        Explore ward displays
                                        <ArrowDown aria-hidden="true" />
                                    </a>
                                </Button>
                                {auth.user && (
                                    <Button size="lg" variant="ghost" asChild>
                                        <Link href={dashboard()}>
                                            Open dashboard
                                            <ArrowRight aria-hidden="true" />
                                        </Link>
                                    </Button>
                                )}
                            </div>
                            <div className="mt-9 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted-foreground">
                                <span className="inline-flex items-center gap-2">
                                    <span className="size-1.5 rounded-full bg-success" />
                                    {wards.length}{' '}
                                    {wards.length === 1
                                        ? 'ward display'
                                        : 'ward displays'}{' '}
                                    available
                                </span>
                                <span className="hidden text-border sm:inline">
                                    /
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <Clock3
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Displays refresh every 30 seconds
                                </span>
                            </div>
                        </div>

                        <div className="relative mx-auto w-full max-w-md lg:ml-auto">
                            <div
                                aria-hidden="true"
                                className="absolute top-10 -left-5 size-20 rounded-2xl border border-primary/10 bg-primary/5"
                            />
                            <div
                                aria-hidden="true"
                                className="absolute -right-4 -bottom-5 size-28 rounded-full border border-primary/10"
                            />
                            <div className="relative rounded-[1.75rem] border border-border/70 bg-card p-5 shadow-[0_24px_64px_-32px_rgba(35,37,71,0.28)] sm:p-6">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <div className="flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                                            <MapPin
                                                className="size-3.5 text-primary"
                                                aria-hidden="true"
                                            />
                                            Public directory
                                        </div>
                                        <h2 className="mt-3 text-2xl font-semibold tracking-tight">
                                            Find a ward
                                        </h2>
                                    </div>
                                    <span className="flex size-10 items-center justify-center rounded-xl bg-secondary text-secondary-foreground">
                                        <Radio
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                </div>

                                {wards.length > 0 ? (
                                    <ul className="mt-6 space-y-2">
                                        {wards.slice(0, 3).map((ward) => (
                                            <li key={ward.id}>
                                                <Link
                                                    href={ward.displayUrl}
                                                    className="group flex min-h-14 items-center justify-between gap-3 rounded-xl border border-border/60 bg-background px-4 py-3 transition-colors hover:border-primary/30 hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                >
                                                    <span className="flex min-w-0 items-center gap-3">
                                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-secondary text-secondary-foreground">
                                                            <MapPin
                                                                className="size-4"
                                                                aria-hidden="true"
                                                            />
                                                        </span>
                                                        <span className="min-w-0">
                                                            <span className="block truncate text-sm font-medium">
                                                                {ward.name}
                                                            </span>
                                                            <span className="mt-0.5 block text-xs text-muted-foreground">
                                                                {ward.code}
                                                            </span>
                                                        </span>
                                                    </span>
                                                    <ArrowUpRight
                                                        className="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary"
                                                        aria-hidden="true"
                                                    />
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="mt-6 rounded-xl border border-dashed border-border px-4 py-6 text-center text-sm leading-6 text-muted-foreground">
                                        Ward displays will appear here when
                                        wards have been added.
                                    </p>
                                )}

                                <a
                                    href="#ward-directory"
                                    className="mt-4 flex min-h-11 w-full items-center justify-center gap-2 rounded-lg text-sm font-medium text-primary transition-colors hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                >
                                    Browse all wards
                                    <ArrowRight
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </a>
                            </div>
                            <div className="relative mx-auto mt-4 flex w-fit items-center gap-2 rounded-full border border-border/70 bg-background/90 px-3 py-2 text-xs text-muted-foreground shadow-sm">
                                <span className="relative flex size-2">
                                    <span className="absolute inline-flex size-full animate-ping rounded-full bg-success/50 motion-reduce:animate-none" />
                                    <span className="relative inline-flex size-2 rounded-full bg-success" />
                                </span>
                                Ward information, refreshed regularly
                            </div>
                        </div>
                    </section>

                    <section
                        id="ward-directory"
                        className="scroll-mt-6 border-t border-border/60 bg-card/50"
                    >
                        <div className="mx-auto w-full max-w-7xl px-5 py-16 sm:px-8 sm:py-20 lg:px-10 lg:py-24">
                            <div className="flex flex-wrap items-end justify-between gap-5">
                                <div className="max-w-2xl">
                                    <p className="text-xs font-semibold tracking-[0.16em] text-primary uppercase">
                                        WARD DIRECTORY
                                    </p>
                                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                                        Public displays by ward
                                    </h2>
                                    <p className="mt-3 text-sm leading-6 text-muted-foreground sm:text-base">
                                        Choose a ward to open its live display.
                                        Information on each display refreshes
                                        automatically.
                                    </p>
                                </div>
                                <div className="inline-flex items-center gap-2 rounded-full bg-secondary px-3 py-2 text-xs font-medium text-secondary-foreground">
                                    <Radio
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    {wards.length} available
                                </div>
                            </div>

                            {wards.length > 0 ? (
                                <ul className="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    {wards.map((ward, index) => (
                                        <li key={ward.id}>
                                            <Link
                                                href={ward.displayUrl}
                                                aria-label={`Open public display for ${ward.name}, ${ward.code}`}
                                                className="group flex h-full min-h-48 flex-col justify-between rounded-2xl border border-border/70 bg-background p-5 shadow-sm transition-[border-color,box-shadow,transform] duration-200 ease-out hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-reduce:transition-none"
                                            >
                                                <span className="flex items-start justify-between gap-4">
                                                    <span className="flex size-11 items-center justify-center rounded-xl bg-secondary text-secondary-foreground transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                                        <MapPin
                                                            className="size-5"
                                                            aria-hidden="true"
                                                        />
                                                    </span>
                                                    <span className="font-mono text-xs tracking-wide text-muted-foreground">
                                                        {String(
                                                            index + 1,
                                                        ).padStart(2, '0')}
                                                        <span className="px-1.5 text-border">
                                                            /
                                                        </span>
                                                        {ward.code}
                                                    </span>
                                                </span>
                                                <span className="mt-8 flex items-end justify-between gap-3">
                                                    <span>
                                                        <span className="block text-lg font-semibold tracking-tight">
                                                            {ward.name}
                                                        </span>
                                                        <span className="mt-1 block text-sm text-muted-foreground">
                                                            Open ward display
                                                        </span>
                                                    </span>
                                                    <ArrowUpRight
                                                        className="mb-0.5 size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-primary"
                                                        aria-hidden="true"
                                                    />
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div className="mt-9 rounded-2xl border border-dashed border-border bg-background px-6 py-14 text-center">
                                    <MapPin
                                        className="mx-auto size-8 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <h3 className="mt-4 text-lg font-semibold">
                                        No wards listed yet
                                    </h3>
                                    <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                                        Public ward displays will be available
                                        here once hospital wards are set up.
                                    </p>
                                </div>
                            )}
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border/60">
                    <div className="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-6 text-xs text-muted-foreground sm:px-8 lg:px-10">
                        <span>
                            © {new Date().getFullYear()} {name}
                        </span>
                        <span className="inline-flex items-center gap-2">
                            <HeartPulse
                                className="size-3.5 text-primary"
                                aria-hidden="true"
                            />
                            Hospital information
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
