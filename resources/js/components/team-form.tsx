import { Form, Head } from '@inertiajs/react';
import TeamController from '@/actions/App/Http/Controllers/Facility/TeamController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TeamRecord } from '@/types/team';

/**
 * CIMA-043: shared create/edit form for teams (name + lifecycle status).
 */
export default function TeamForm({
    title,
    description,
    team,
}: {
    title: string;
    description: string;
    team?: TeamRecord;
}) {
    const action = team
        ? TeamController.update.form(team.id)
        : TeamController.store.form();

    return (
        <>
            <Head title={title} />

            <h1 className="sr-only">{title}</h1>

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading title={title} description={description} />

                <Form
                    {...action}
                    options={{
                        preserveScroll: true,
                    }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <div className="space-y-6 rounded-lg border border-border bg-card p-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block"
                                        defaultValue={team?.name ?? ''}
                                        name="name"
                                        required
                                        maxLength={120}
                                        placeholder="e.g. Stroke response team"
                                        data-test="team-name"
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="status">Status</Label>

                                    <Select
                                        defaultValue={team?.status ?? 'active'}
                                        name="status"
                                        data-test="team-status"
                                    >
                                        <SelectTrigger id="status">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">
                                                active
                                            </SelectItem>
                                            <SelectItem value="inactive">
                                                inactive
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.status}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="save-team"
                                >
                                    {team ? 'Save team' : 'Add team'}
                                </Button>
                            </div>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}
