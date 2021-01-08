import Branch from "./Branch";


export default class Group {
    id: number;
    name: String;
    branchId: number;
    branch: Branch;

    constructor(id: number, name: String, branchId: number, branch: Branch) {
        this.id = id;
        this.name = name;
        this.branchId = branchId;
        this.branch = branch;
    }

    getWithBranchName = function() {
        return this.branch.shortName + " | " + this.name;
    }
}
