// 再帰的にコメントを検索する関数
export const findCommentRecursive = (comments, commentId) => {
    for (let i = 0; i < comments.length; i++) {
        if (comments[i].id === commentId) {
            return comments[i];
        }
        if (comments[i].children && comments[i].children.length > 0) {
            const foundComment = findCommentRecursive(
                comments[i].children,
                commentId
            );
            if (foundComment) {
                return foundComment;
            }
        }
    }
    return null;
};

// 再帰的にコメントを削除
export const deleteCommentRecursive = (comments, commentId) => {
    for (let i = 0; i < comments.length; i++) {
        if (comments[i].id === commentId) {
            comments.splice(i, 1); // コメント削除
            return true; // 削除成功
        }
        if (comments[i].children && comments[i].children.length > 0) {
            const deleted = deleteCommentRecursive(
                comments[i].children,
                commentId
            );
            if (deleted) {
                return true; // 子コメント削除成功
            }
        }
    }
    return false; // 削除対象が見つからなかった場合
};
