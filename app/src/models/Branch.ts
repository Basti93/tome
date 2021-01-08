export default class Branch {
    id: number;
    name: String;
    shortName: String;

    constructor(id: number, name: String, shortName: String) {
        this.id = id;
        this.name = name;
        this.shortName = shortName;
    }

    getShortNameAndName() {
        return this.name + ' (' + this.shortName + ')';
    }

}
