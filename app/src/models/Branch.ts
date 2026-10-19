export default class Branch {
    id: number;
    name: string;
    shortName: string;
    colorHex: string;

    constructor(id: number, name: string, shortName: string, colorHex: string) {
        this.id = id;
        this.name = name;
        this.shortName = shortName;
        this.colorHex = colorHex;
    }

    getShortNameAndName() {
        return this.name + ' (' + this.shortName + ')';
    }

}
