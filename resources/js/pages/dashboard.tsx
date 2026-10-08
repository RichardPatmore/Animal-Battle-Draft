import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, home } from '@/routes';

export default function Dashboard() {
	const { auth } = usePage<{
		auth: { user: { name: string; email: string } };
	}>().props;

	return (
		<>
			<Head title="Account" />
			<div className="flex flex-col gap-4 p-4">
				<Card>
					<CardHeader>
						<CardTitle>Your account</CardTitle>
					</CardHeader>
					<CardContent className="space-y-2 text-sm">
						<p>
							<span className="text-muted-foreground">Name:</span>{' '}
							{auth.user.name}
						</p>
						<p>
							<span className="text-muted-foreground">
								Email:
							</span>{' '}
							{auth.user.email}
						</p>
						<p className="pt-2 text-muted-foreground">
							Accounts are only used for the research admin. The
							game itself needs no login.
						</p>
						<div className="flex gap-4 pt-2 font-medium">
							<a href="/admin" className="text-primary underline">
								Open the admin panel
							</a>
							<a
								href={home().url}
								className="text-primary underline"
							>
								Back to the game
							</a>
						</div>
					</CardContent>
				</Card>
			</div>
		</>
	);
}

Dashboard.layout = {
	breadcrumbs: [
		{
			title: 'Account',
			href: dashboard(),
		},
	],
};
