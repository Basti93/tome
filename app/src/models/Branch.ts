export default class Branch {
    id: number;
    name: String;
    shortName: String;
    colorHex: String;

    constructor(id: number, name: String, shortName: String, colorHex: String) {
        this.id = id;
        this.name = name;
        this.shortName = shortName;
        this.colorHex = colorHex;
    }

    getShortNameAndName() {
        return this.name + ' (' + this.shortName + ')';
    }

}
