import { Head, usePage } from '@inertiajs/react';

export default function ComingSoon() {
    const { appVersion } = usePage().props;

    return (
        <>
            <Head title="Coming soon" />
            <main className="flex min-h-screen flex-col items-center justify-center bg-background px-6 text-center text-foreground">
                <img
                    src="/icon.svg"
                    alt=""
                    width={72}
                    height={72}
                    className="mb-6 rounded-2xl"
                />
                <h1 className="text-5xl font-semibold tracking-tight">
                    Bathys
                </h1>
                <p className="mt-2 text-sm tracking-widest text-muted-foreground uppercase">
                    Greek for “deep”
                </p>
                <p className="mt-8 max-w-md text-lg text-pretty">
                    A personal catalog of the books on the shelf and the books
                    wanted, with how deep each one has been read.
                </p>
                <p className="mt-10 text-sm text-muted-foreground">
                    Coming soon.
                </p>
            </main>
            <footer className="fixed inset-x-0 bottom-0 pb-4 text-center text-xs text-muted-foreground/60">
                v{appVersion}
            </footer>
        </>
    );
}
