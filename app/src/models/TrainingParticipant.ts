export default class TrainingParticipant {

    trainingId: number;
    userId: number;
    attend: boolean;
    cancelreason: string;

    static from(jsonString: string) {
        let jsonObj = JSON.parse(jsonString);
        return new TrainingParticipant(jsonObj.trainingId, jsonObj.userId, jsonObj.attend === 1 ? true : false, jsonObj.cancelreason)
    }

    constructor(cTrainingId: number, cUserId: number, cAttend: boolean, cancelreason: string) {
        this.trainingId = cTrainingId;
        this.userId = cUserId;
        this.attend = cAttend;
        this.cancelreason = cancelreason;
    }

}
