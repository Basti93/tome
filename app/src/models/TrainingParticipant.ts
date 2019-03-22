export default class TrainingParticipant {

    trainingId: number;
    userId: number;
    attend: boolean;

    static from(jsonString: string) {
        let jsonObj = JSON.parse(jsonString);
        return new TrainingParticipant(jsonObj.trainingId, jsonObj.userId, jsonObj.attend === 1 ? true : false)
    }

    constructor(cTrainingId: number, cUserId: number, cAttend: boolean) {
        this.trainingId = cTrainingId;
        this.userId = cUserId;
        this.attend = cAttend;
    }

}
