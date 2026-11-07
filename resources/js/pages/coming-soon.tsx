import { Head } from '@inertiajs/react';

export default function ComingSoon() {
    return (
        <>
            <Head title="Coming soon" />
            <main className="flex min-h-screen flex-col items-center justify-center bg-background px-6 text-center text-foreground">
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
        </>
    );
}
