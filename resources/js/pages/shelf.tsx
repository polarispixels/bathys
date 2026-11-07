import { Head, usePage } from '@inertiajs/react';
import type { components } from '@/types/api';

type Props = {
    books: components['schemas']['BookCollection'];
};

export default function Shelf({ books }: Props) {
    const { appVersion } = usePage().props;

    return (
        <>
            <Head title="Shelf" />
            <main className="mx-auto min-h-screen max-w-2xl bg-background px-6 pt-12 pb-16 text-foreground">
                <header className="flex items-center gap-4">
                    <img
                        src="/icon.svg"
                        alt=""
                        width={48}
                        height={48}
                        className="rounded-xl"
                    />
                    <h1 className="text-4xl font-semibold tracking-tight">
                        Bathys
                    </h1>
                </header>
                <p className="mt-4 text-pretty text-muted-foreground">
                    A personal catalog of the books on the shelf and the books
                    wanted, with how deep each one has been read.
                </p>
                {books.data.length === 0 ? (
                    <p className="mt-8">No books on the shelf yet.</p>
                ) : (
                    <ol className="mt-8 divide-y divide-border">
                        {books.data.map((book) => (
                            <li key={book.id} className="py-3">
                                <p className="font-medium">
                                    {book.attributes.title}
                                </p>
                                {book.attributes.authors.length > 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        {book.attributes.authors.join(', ')}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ol>
                )}
            </main>
            <footer className="pb-4 text-center text-xs text-muted-foreground/60">
                v{appVersion}
            </footer>
        </>
    );
}
