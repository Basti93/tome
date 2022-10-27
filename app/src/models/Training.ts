import TrainingParticipant from "@/models/TrainingParticipant";
import * as moment from "moment";

export default class Training {
    id: number;
    start: moment.Moment;
    end: moment.Moment;
    locationId: number;
    groupIds: number[];
    contentIds: number[];
    trainerIds: number[];
    participants: TrainingParticipant[];
    comment: string;
    evaluated: boolean;
    prepared: boolean;
    automaticAttend: boolean;

    static from(jsonString: string) {
        const jsonObj = JSON.parse(jsonString);
        const participants = [] as TrainingParticipant[];
        for (const participant of jsonObj.trainingParticipants) {
            participants.push(TrainingParticipant.from(participant));
        }
        return new Training(jsonObj.id, jsonObj.start, jsonObj.end, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, jsonObj.trainerIds, participants, jsonObj.comment, null, null,  jsonObj.automaticAttend === 1 ? true : false)
    }


    constructor(id: number, start: moment.Moment, end: moment.Moment, locationId: number, groupIds: number[], contentIds: number[], trainerIds: number[], participants: TrainingParticipant[], comment: string, prepared: boolean, evaluated: boolean, automaticAttend: boolean) {
        this.id = id;
        this.start = start;
        this.end = end;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainerIds = trainerIds;
        this.participants = participants;
        this.comment = comment;
        this.evaluated = evaluated;
        this.prepared = prepared;
        this.automaticAttend = automaticAttend;
    }
}
