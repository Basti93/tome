import type { Dayjs } from '@/helpers/dayjs'

export default class TrainingSeries {
    id: number;
    startTime: string;
    endTime: string;
    locationId: number;
    groupIds: number[] = [];
    contentIds: number[] = [];
    trainerIds: number[] = [];
    comment: string;
    weekdays: number[] = [];
    deferUntil: Dayjs;
    automaticAttend: boolean;

    static from(jsonObj) {
        return new TrainingSeries(jsonObj.id, jsonObj.startTime, jsonObj.endTime, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, jsonObj.trainerIds, jsonObj.comment, jsonObj.weekdays, jsonObj.deferUntil,  jsonObj.automaticAttend === 1 ? true : false)
    }

    constructor(id: number, startTime: string, endTime: string, locationId: number, groupIds: number[], contentIds: number[], trainerIds: number[], comment: string, weekdays: number[], deferUntil: Dayjs, automaticAttend: boolean) {
        this.id = id;
        this.startTime = startTime;
        this.endTime = endTime;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainerIds = trainerIds;
        this.comment = comment;
        this.weekdays = weekdays;
        this.deferUntil = deferUntil;
        this.automaticAttend = automaticAttend;
    }
}
