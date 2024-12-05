export const fetchPostsByForumId = (router, forumId) => {
    if (forumId) {
        router.get(route("forum.index", { forum_id: forumId }), {
            preserveState: true,
            only: ["posts"],
        });
    }
};
