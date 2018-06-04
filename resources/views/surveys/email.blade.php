<!DOCTYPE html>
<html>

    <head>
        <meta charset="UTF-8">
        <title>Vision to Actions</title>
        <style>
            .button {
                display: inline-block;
                padding: 10px 32px;
                font-size: 24px;
                cursor: pointer;
                text-align: center;
                text-decoration: none;
                outline: none;
                color: #fff;
                background-color: #4CAF50;
                border: none;
                border-radius: 15px;
                box-shadow: 0 5px #999;
            }

            .button:hover {background-color: #3e8e41}

            .button:active {
                background-color: #3e8e41;
                box-shadow: 0 5px #666;
                transform: translateY(4px);
            }
        </style>

    </head>

    <body>
        <h4>Dear {{$participant->participant_first_name}} {{$participant->participant_last_name}},</h4>

        <p>Thank you for participating in the Vision to Actions survey from New Life CFO.
            We are assisting your CEO and leadership team with a confidential assessment of the goals, vision,
            accountability and reporting/processes for your company. Please click to start the survey.</p>

        <br>

        <div style="text-align: center;">
            <a href="{{ route('start_survey', $participant->completion_token) }}" class="button">Start Now</a>
        </div>

        <br>

        <p>If you have any questions regarding to this survey, please feel free to reply back this email.</p>

        <p><b>Notice: your response will remain confidential. No individual results will be available to the CEO. The CEO will only see the following:</b></p>
        <ul>
            <li><b>CEO response</b></li>
            <li><b>Combined results of leadership team</b></li>
            <li><b>Combined results of entire company</b></li>
        </ul>

    <p>We appreciate your participation in this assessment.</p>
    <p>New Life CFO Services</p>


    </body>
</html>

