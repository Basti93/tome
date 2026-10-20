import TrainingParticipant from "./TrainingParticipant";
import TrainingTrainer from "./TrainingTrainer";
import type { Dayjs } from '@/helpers/dayjs'

export default class Training {
    id: number;
    start: Dayjs;
    end: Dayjs;
    locationId: number;
    groupIds: number[];
    contentIds: number[];
    trainers: TrainingTrainer[];
    participants: TrainingParticipant[];
    comment: string;
    evaluated: boolean;
    prepared: boolean;
    note: string;

    static from(jsonString: string) {
        const jsonObj = JSON.parse(jsonString);
        const participants = [] as TrainingParticipant[];
        for (const participant of jsonObj.trainingParticipants) {
            participants.push(TrainingParticipant.from(participant));
        }
        const trainers = [] as TrainingTrainer[];
        for (const trainer of jsonObj.trainers) {
            trainers.push(TrainingTrainer.from(trainer));
        }
        return new Training(jsonObj.id, jsonObj.start, jsonObj.end, jsonObj.locationId, jsonObj.groupIds, jsonObj.contentIds, trainers, participants, jsonObj.comment, jsonObj.prepared === 1 ? true : false, jsonObj.evaluated === 1 ? true : false, jsonObj.note ?? '')
    }


    constructor(id: number, start: Dayjs, end: Dayjs, locationId: number, groupIds: number[], contentIds: number[], trainers: TrainingTrainer[], participants: TrainingParticipant[], comment: string, prepared: boolean, evaluated: boolean, note: string = '') {
        this.id = id;
        this.start = start;
        this.end = end;
        this.locationId = locationId;
        this.groupIds = groupIds;
        this.contentIds = contentIds;
        this.trainers = trainers;
        this.participants = participants;
        this.comment = comment;
        this.evaluated = evaluated;
        this.prepared = prepared;
        this.note = note;
    }
}
