import { router } from "@inertiajs/vue3";

export const redirectToForum = async (
    units,
    users,
    userUnitId,
    routeName = "forum.index"
) => {
    try {
        const response = await fetch(`/user-forum-id`, {
            headers: {
                Accept: "application/json",
            },
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Failed to fetch forum ID");
        }

        const forumId = data.forum_id || null;

        if (userUnitId) {
            const unit = units.find((u) => u.id === userUnitId);
            const unitUsers = users.filter(
                (user) => user.unit_id === userUnitId
            );

            if (unit) {
                sessionStorage.setItem(
                    "selectedUnitUsers",
                    JSON.stringify(unitUsers)
                );
                sessionStorage.setItem("selectedUnitName", unit.name);
            }
        } else {
            console.warn("User does not belong to a unit.");
        }

        if (forumId) {
            router.get(route(routeName, { forum_id: forumId }), {
                preserveState: false,
            });
        } else {
            console.warn("Forum ID not found. Navigation skipped.");
        }
    } catch (error) {
        console.error("Error fetching user forum ID:", error);
        alert(error.message);
    }
};
