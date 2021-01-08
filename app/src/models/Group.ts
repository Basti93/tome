import Branch from "./Branch";


export default class Group {
    id: number;
    name: String;
    branchId: number;
    branch: Branch;
    userIds: Array<Number>;

    constructor(id: number, name: String, branchId: number, branch: Branch, userIds: Array<Number>) {
        this.id = id;
        this.name = name;
        this.branchId = branchId;
        this.branch = branch;
        this.userIds = userIds;
    }

    getWithBranchName = function() {
        return this.branch.shortName + " | " + this.name;
    }
}
